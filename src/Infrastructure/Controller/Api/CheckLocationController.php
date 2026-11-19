<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\IntersectionGeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Exception\ZoneWithoutStreetsException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Query\Location\CheckLocationQuery;
use App\Infrastructure\DTO\Event\SaveLocationDTO;
use App\Infrastructure\Mapper\Transformers\LocationsTransformer;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CheckLocationController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        '/api/locations/check',
        name: 'api_locations_check',
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_API')]
    #[OA\Tag(name: 'Private')]
    #[OA\RequestBody(
        required: true,
        description: 'Une localisation, au même format que les éléments de `measures[].locations` '
            . 'de `POST /api/regulations`. Rien n\'est enregistré : la géométrie est calculée et '
            . 'contrôlée exactement comme lors de la création d\'un arrêté, ce qui permet de détecter '
            . 'les refus (géocodage, localisation hors du territoire de l\'organisation, zone sans rue) '
            . 'avant d\'envoyer l\'arrêté complet.',
        content: new OA\JsonContent(
            type: 'object',
            example: [
                'roadType' => 'lane',
                'namedStreet' => [
                    'cityCode' => '93070',
                    'cityLabel' => 'Saint-Ouen-sur-Seine',
                    'roadName' => 'Rue Eugène Berthoud',
                    'direction' => 'BOTH',
                ],
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Résultat du contrôle. `accepted` vaut `false` si la localisation serait refusée '
            . 'à la création de l\'arrêté ; `reason` en donne alors le motif.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'accepted', type: 'boolean', example: false),
                new OA\Property(
                    property: 'reason',
                    type: 'string',
                    nullable: true,
                    enum: [
                        'organization_cannot_intervene_on_geometry',
                        'geocoding_failed',
                        'lane_geocoding_failed',
                        'intersection_not_found',
                        'numbered_road_geocoding_failed',
                        'abscissa_out_of_range',
                        'zone_without_streets',
                    ],
                    example: 'organization_cannot_intervene_on_geometry',
                ),
                new OA\Property(property: 'detail', type: 'string', nullable: true),
                new OA\Property(
                    property: 'geometry',
                    type: 'object',
                    nullable: true,
                    description: 'Géométrie GeoJSON calculée pour la localisation, lorsqu\'elle a pu l\'être.',
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié / identifiants client invalides',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
            ],
        ),
    )]
    #[OA\Response(
        response: 422,
        description: 'Erreur de validation (localisation incomplète ou mal formée)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 422),
                new OA\Property(property: 'detail', type: 'string', example: 'Validation failed'),
                new OA\Property(
                    property: 'violations',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'propertyPath', type: 'string', example: 'location.roadType'),
                            new OA\Property(property: 'title', type: 'string', example: 'Cette valeur ne doit pas être vide.'),
                            new OA\Property(property: 'parameters', type: 'object'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    public function __invoke(
        #[MapRequestPayload] SaveLocationDTO $dto,
    ): JsonResponse {
        $user = $this->security->getUser();
        \assert($user instanceof OrganizationAwareUserInterface);
        $organization = $user->getOrganization();

        /** @var SaveLocationCommand $command */
        $command = LocationsTransformer::toCommands([$dto])[0];
        $command->organization = $organization;

        try {
            $geometry = $this->queryBus->handle(new CheckLocationQuery($command));
        } catch (OrganizationCannotInterveneOnGeometryException) {
            return $this->refused(
                'organization_cannot_intervene_on_geometry',
                $this->translator->trans(
                    'regulation.location.error.organization_cannot_intervene_on_geometry',
                    ['%organizationName%' => $organization->getName()],
                    'validators',
                ),
            );
        } catch (ZoneWithoutStreetsException) {
            return $this->refused('zone_without_streets');
        } catch (GeocodingFailureException $e) {
            return $this->refused($this->getGeocodingFailureReason($e));
        }

        return new JsonResponse([
            'accepted' => true,
            'reason' => null,
            'detail' => null,
            'geometry' => json_decode($geometry, true),
        ]);
    }

    private function getGeocodingFailureReason(GeocodingFailureException $e): string
    {
        return match (true) {
            $e instanceof IntersectionGeocodingFailureException => 'intersection_not_found',
            $e instanceof LaneGeocodingFailureException => 'lane_geocoding_failed',
            $e instanceof AbscissaOutOfRangeException => 'abscissa_out_of_range',
            $e instanceof RoadGeocodingFailureException => 'numbered_road_geocoding_failed',
            default => 'geocoding_failed',
        };
    }

    private function refused(string $reason, ?string $detail = null): JsonResponse
    {
        return new JsonResponse([
            'accepted' => false,
            'reason' => $reason,
            'detail' => $detail ?? $this->translator->trans('regulation.location.error.' . $reason, [], 'validators'),
            'geometry' => null,
        ]);
    }
}
