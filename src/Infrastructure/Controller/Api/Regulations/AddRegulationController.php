<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Exception\AllLocationsRejectedException;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationWithMeasureCommand;
use App\Application\Regulation\Query\GetRejectedLocationsQuery;
use App\Application\Regulation\View\RejectedLocationView;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Infrastructure\DTO\Event\RegulationGeneralInfoDTO;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AddRegulationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Security $security,
        private ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route(
        '/api/regulations',
        name: 'api_regulations_add',
        methods: ['POST'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Post(
        summary: 'Créer un arrêté de circulation',
        description: <<<'DESCRIPTION'
            ### Localisations rejetées
            Une localisation invalide (valeur manquante, géocodage en échec, hors compétence...) est
            rejetée sans empêcher l'intégration des autres ; une erreur portant sur une mesure (type,
            périodes, véhicules...) rejette toutes ses localisations. Les rejets sont listés dans
            `rejectedLocations` et consultables via `GET /api/regulations/{identifier}/rejected-locations`.
            Si aucune localisation ne peut être intégrée, l'arrêté n'est pas enregistré (erreur `422`).
            DESCRIPTION,
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', maxLength: 60, example: 'F2025/001'),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
                new OA\Property(property: 'category', type: 'string', enum: ['temporaryRegulation', 'permanentRegulation'], example: 'temporaryRegulation'),
                new OA\Property(property: 'subject', type: 'string', enum: ['roadMaintenance', 'incident', 'event', 'winterMaintenance', 'other'], nullable: true, example: 'roadMaintenance'),
                new OA\Property(property: 'otherCategoryText', type: 'string', nullable: true, maxLength: 100, example: null),
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Travaux de voirie rue Exemple'),
                new OA\Property(
                    property: 'measures',
                    type: 'array',
                    nullable: false,
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['alternateRoad', 'noEntry', 'speedLimitation', 'parkingProhibited', 'noOvertaking'], example: 'speedLimitation'),
                            new OA\Property(property: 'maxSpeed', type: 'integer', nullable: true, example: 30),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', nullable: true, example: '2025-10-09T08:00:00Z'),
                            new OA\Property(
                                property: 'vehicleSet',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'allVehicles', type: 'boolean', nullable: true, example: false),
                                    new OA\Property(property: 'restrictedTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['heavyGoodsVehicle', 'dimensions', 'critair', 'hazardousMaterials', 'other']), nullable: true),
                                    new OA\Property(property: 'exemptedTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['commercial', 'emergencyServices', 'bicycle', 'pedestrians', 'taxi', 'carSharing', 'roadMaintenanceOrConstruction', 'cityLogistics', 'police', 'desserteLocale', 'localResident', 'other']), nullable: true),
                                    new OA\Property(property: 'otherRestrictedTypeText', type: 'string', nullable: true),
                                    new OA\Property(property: 'otherExemptedTypeText', type: 'string', nullable: true),
                                    new OA\Property(property: 'heavyweightMaxWeight', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'maxWidth', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'maxLength', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'maxHeight', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'critairTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['critair2', 'critair3', 'critair4', 'critair5']), nullable: true),
                                ],
                            ),
                            new OA\Property(
                                property: 'periods',
                                type: 'array',
                                nullable: true,
                                items: new OA\Items(
                                    type: 'object',
                                    required: ['startDate', 'recurrenceType'],
                                    properties: [
                                        new OA\Property(property: 'startDate', type: 'string', format: 'date-time'),
                                        new OA\Property(property: 'startTime', type: 'string', format: 'date-time', nullable: true, description: 'Obligatoire si isPermanent = false'),
                                        new OA\Property(property: 'endDate', type: 'string', format: 'date-time', nullable: true, description: 'Obligatoire si isPermanent = false'),
                                        new OA\Property(property: 'endTime', type: 'string', format: 'date-time', nullable: true, description: 'Obligatoire si isPermanent = false'),
                                        new OA\Property(property: 'recurrenceType', type: 'string', enum: ['everyDay', 'certainDays']),
                                        new OA\Property(property: 'isPermanent', type: 'boolean', nullable: true),
                                        new OA\Property(property: 'isPublicHolidaysExcluded', type: 'boolean', nullable: true, description: 'Exclut les jours fériés de la période (par défaut : false)'),
                                        new OA\Property(
                                            property: 'dailyRange',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'recurrenceType', type: 'string', enum: ['everyDay', 'certainDays'], nullable: true),
                                                new OA\Property(property: 'applicableDays', type: 'array', items: new OA\Items(type: 'string', enum: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']), nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'timeSlots',
                                            type: 'array',
                                            nullable: true,
                                            items: new OA\Items(
                                                type: 'object',
                                                properties: [
                                                    new OA\Property(property: 'startTime', type: 'string', format: 'date-time', nullable: true),
                                                    new OA\Property(property: 'endTime', type: 'string', format: 'date-time', nullable: true),
                                                ],
                                            ),
                                        ),
                                    ],
                                ),
                            ),
                            new OA\Property(
                                property: 'locations',
                                type: 'array',
                                nullable: true,
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'roadType', type: 'string', enum: ['lane', 'departmentalRoad', 'nationalRoad', 'rawGeoJSON', 'zone', 'wholeCity'], example: 'lane'),
                                        new OA\Property(
                                            property: 'namedStreet',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'cityCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'cityLabel', type: 'string', nullable: true),
                                                new OA\Property(property: 'roadName', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromPointType', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromHouseNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromRoadName', type: 'string', nullable: true),
                                                new OA\Property(property: 'toPointType', type: 'string', nullable: true),
                                                new OA\Property(property: 'toHouseNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'toRoadName', type: 'string', nullable: true),
                                                new OA\Property(property: 'direction', type: 'string', enum: ['BOTH', 'A_TO_B', 'B_TO_A'], nullable: true, description: 'Sens de circulation concerné, relativement aux points de début (A) et de fin (B). Pour une voie entière, A est l\'extrémité la plus à l\'ouest de la voie et B l\'extrémité opposée. `BOTH` par défaut.'),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'departmentalRoad',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'administrator', type: 'string', nullable: true),
                                                new OA\Property(property: 'roadNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'fromSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'toDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'toPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'toAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'toSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'direction', type: 'string', enum: ['BOTH', 'A_TO_B', 'B_TO_A'], nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'nationalRoad',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'administrator', type: 'string', nullable: true),
                                                new OA\Property(property: 'roadNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'fromSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'toDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'toPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'toAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'toSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'direction', type: 'string', enum: ['BOTH', 'A_TO_B', 'B_TO_A'], nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'rawGeoJSON',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'label', type: 'string', nullable: true),
                                                new OA\Property(property: 'geometry', type: 'string', nullable: true),
                                                new OA\Property(property: 'exceptions', type: 'array', nullable: true, description: 'Emprises exclues de la restriction (« Sauf... »), soustraites du tracé.', items: new OA\Items(ref: '#/components/schemas/WholeCityException')),
                                                new OA\Property(property: 'direction', type: 'string', nullable: true, enum: ['BOTH', 'A_TO_B', 'B_TO_A'], description: 'Sens de circulation concerné, relativement au tracé : A est le premier point de la géométrie, B le dernier. `BOTH` par défaut.'),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'zone',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Tracé de zone. Renseigné lorsque `roadType` vaut `zone`. '
                                                . 'Le périmètre dessiné (`geometry`) est un polygone GeoJSON ; '
                                                . 'les tronçons de rues couverts par ce périmètre sont calculés '
                                                . 'automatiquement et constituent la géométrie effective de la '
                                                . 'localisation. Une zone ne contenant aucune rue est refusée (erreur 400).',
                                            properties: [
                                                new OA\Property(property: 'label', type: 'string', maxLength: 255, nullable: true, description: 'Libellé descriptif de la zone.', example: 'Quartier des Docks'),
                                                new OA\Property(property: 'geometry', type: 'string', nullable: true, description: 'Polygone GeoJSON sérialisé en chaîne JSON délimitant la zone.', example: '{"type":"Polygon","coordinates":[[[2.325,48.9125],[2.331,48.9125],[2.331,48.9152],[2.325,48.9152],[2.325,48.9125]]]}'),
                                                new OA\Property(property: 'exceptions', type: 'array', nullable: true, description: 'Emprises exclues de la restriction (« Sauf... »), soustraites des tronçons couverts par la zone.', items: new OA\Items(ref: '#/components/schemas/WholeCityException')),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'wholeCity',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Ville entière. Renseigné lorsque `roadType` vaut `wholeCity` : '
                                                . 'la restriction s\'applique à toutes les voies de la commune, '
                                                . 'sauf les emprises listées dans `exceptions`.',
                                            properties: [
                                                new OA\Property(property: 'cityCode', type: 'string', nullable: true, description: 'Code INSEE de la commune.', example: '93070'),
                                                new OA\Property(property: 'cityLabel', type: 'string', nullable: true, example: 'Saint-Ouen-sur-Seine'),
                                                new OA\Property(property: 'exceptions', type: 'array', nullable: true, description: 'Emprises exclues de la restriction (« Sauf... »).', items: new OA\Items(ref: '#/components/schemas/WholeCityException')),
                                            ],
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Création réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                new OA\Property(property: 'rejectedLocations', type: 'array', description: 'Localisations rejetées (les autres ont été intégrées). Également consultables via `GET /api/regulations/{identifier}/rejected-locations`.', items: new OA\Items(ref: '#/components/schemas/RejectedLocation')),
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
        description: 'Erreur de validation, ou aucune localisation n\'a pu être intégrée (`rejectedLocations` détaille alors chaque rejet et rien n\'est enregistré)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 422),
                new OA\Property(property: 'detail', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'rejectedLocations', type: 'array', nullable: true, items: new OA\Items(ref: '#/components/schemas/RejectedLocation')),
                new OA\Property(
                    property: 'violations',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'propertyPath', type: 'string', example: 'locations[0].organization'),
                            new OA\Property(property: 'title', type: 'string', example: 'Cette valeur ne doit pas être vide.'),
                            new OA\Property(property: 'parameters', type: 'object'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    public function __invoke(
        Request $request,
        #[MapRequestPayload] RegulationGeneralInfoDTO $dto,
    ): JsonResponse {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();
        $organization = $user->getOrganization();

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->source = RegulationOrderRecordSourceEnum::API->value;
        $generalInfo->organization = $organization;
        $this->objectMapper->map($dto, $generalInfo);

        try {
            $regulationOrderRecord = $this->commandBus->handle(
                new SaveRegulationWithMeasureCommand($generalInfo, $dto->status, $dto->measures, self::getRawMeasures($request)),
            );
        } catch (AllLocationsRejectedException $e) {
            return new JsonResponse([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => 'Aucune localisation n\'a pu être intégrée : l\'arrêté n\'a pas été enregistré.',
                'rejectedLocations' => array_map(static fn (RejectedLocationView $view) => $view->toArray(), $e->getRejectedLocations()),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        $rejectedLocations = $this->queryBus->handle(
            new GetRejectedLocationsQuery($organization->getUuid(), $regulationOrderRecord->getUuid()),
        );

        return new JsonResponse(
            [
                'uuid' => $regulationOrderRecord->getUuid(),
                'rejectedLocations' => array_map(static fn (RejectedLocationView $view) => $view->toArray(), $rejectedLocations),
            ],
            Response::HTTP_CREATED,
        );
    }

    public static function getRawMeasures(Request $request): array
    {
        try {
            $measures = $request->toArray()['measures'] ?? [];
        } catch (JsonException) {
            return [];
        }

        return \is_array($measures) ? array_values($measures) : [];
    }
}
