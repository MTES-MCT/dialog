<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteRegulationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/regulations/{identifier}',
        name: 'api_regulations_delete',
        methods: ['DELETE'],
        requirements: ['identifier' => '.+'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Response(
        response: 204,
        description: 'Arrêté supprimé',
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
        response: 403,
        description: 'Accès refusé (l\'arrêté ne peut pas être supprimé)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 403),
                new OA\Property(property: 'detail', type: 'string', example: 'L\'arrêté ne peut pas être supprimé.'),
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
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();

        try {
            /** @var RegulationOrderRecord $regulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(
                new GetRegulationOrderRecordByIdentifierQuery($identifier, $user->getOrganization()),
            );
        } catch (RegulationOrderRecordNotFoundException) {
            return new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => 'Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->commandBus->handle(
                new DeleteRegulationCommand(
                    [$user->getOrganization()->getUuid()],
                    $regulationOrderRecord,
                ),
            );
            $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));
        } catch (RegulationOrderRecordCannotBeDeletedException) {
            return new JsonResponse([
                'status' => Response::HTTP_FORBIDDEN,
                'detail' => 'L\'arrêté ne peut pas être supprimé.',
            ], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
