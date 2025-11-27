<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetOrganizationIdentifiersQuery;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrganizationIdentifiersController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/api/organization/identifiers',
        name: 'api_organization_identifiers',
        methods: ['GET'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Response(
        response: 200,
        description: 'Liste des identifiants existants pour l’organisation authentifiée',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'identifiers',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['F2025/001', 'F2025/002'],
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
    public function __invoke(): JsonResponse
    {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();
        $organization = $user->getOrganization();

        $identifiers = $this->queryBus->handle(new GetOrganizationIdentifiersQuery($organization));

        return new JsonResponse(
            ['identifiers' => $identifiers],
            Response::HTTP_OK,
        );
    }
}
