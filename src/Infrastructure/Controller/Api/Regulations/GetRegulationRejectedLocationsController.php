<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Application\Regulation\Query\GetRejectedLocationsQuery;
use App\Application\Regulation\View\RejectedLocationView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GetRegulationRejectedLocationsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    // Priorité supérieure à api_regulations_get, dont l'identifiant accepte les « / ».
    #[Route(
        '/api/regulations/{identifier}/rejected-locations',
        name: 'api_regulations_rejected_locations',
        methods: ['GET'],
        requirements: ['identifier' => '.+'],
        priority: 1,
    )]
    #[IsGranted('ROLE_API')]
    #[OA\Tag(name: 'Private')]
    #[OA\Get(
        summary: 'Lister les localisations rejetées d\'un arrêté',
        description: 'Localisations rejetées lors du dernier envoi de l\'arrêté via `POST` ou `PUT /api/regulations`.',
    )]
    #[OA\Response(
        response: 200,
        description: 'Localisations rejetées',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'rejectedLocations', type: 'array', items: new OA\Items(ref: '#/components/schemas/RejectedLocation')),
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
        response: 404,
        description: 'Arrêté introuvable',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'detail', type: 'string', example: 'Not Found'),
            ],
        ),
    )]
    public function __invoke(string $identifier): JsonResponse
    {
        $user = $this->security->getUser();
        \assert($user instanceof OrganizationAwareUserInterface);
        $organization = $user->getOrganization();

        try {
            /** @var RegulationOrderRecord $regulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(
                new GetRegulationOrderRecordByIdentifierQuery($identifier, $organization),
            );
        } catch (RegulationOrderRecordNotFoundException) {
            return new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => 'Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        $rejectedLocations = $this->queryBus->handle(
            new GetRejectedLocationsQuery($organization->getUuid(), $regulationOrderRecord->getUuid()),
        );

        return new JsonResponse([
            'rejectedLocations' => array_map(static fn (RejectedLocationView $view) => $view->toArray(), $rejectedLocations),
        ]);
    }
}
