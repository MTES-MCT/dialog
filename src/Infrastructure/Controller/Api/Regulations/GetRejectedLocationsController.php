<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRejectedLocationsQuery;
use App\Application\Regulation\View\RejectedLocationView;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GetRejectedLocationsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/rejected-locations',
        name: 'api_rejected_locations',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_API')]
    #[OA\Tag(name: 'Private')]
    #[OA\Get(
        summary: 'Lister les localisations rejetées de tous les arrêtés de l\'organisation',
        description: 'Localisations rejetées lors du dernier envoi de chaque arrêté via `POST` ou `PUT /api/regulations`, du rejet le plus récent au plus ancien. Le champ `identifier` indique l\'arrêté concerné.',
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
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        \assert($user instanceof OrganizationAwareUserInterface);

        $rejectedLocations = $this->queryBus->handle(
            new GetRejectedLocationsQuery($user->getOrganization()->getUuid()),
        );

        return new JsonResponse([
            'rejectedLocations' => array_map(static fn (RejectedLocationView $view) => $view->toArray(), $rejectedLocations),
        ]);
    }
}
