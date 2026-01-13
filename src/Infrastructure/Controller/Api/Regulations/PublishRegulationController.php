<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublishRegulationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/regulations/publish/{identifier}',
        name: 'api_regulations_publish',
        methods: ['PUT'],
        requirements: ['identifier' => '.+'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Response(
        response: 200,
        description: 'Arrêté publié',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', example: 'ARR-2024-001'),
                new OA\Property(property: 'status', type: 'string', example: RegulationOrderRecordStatusEnum::PUBLISHED->value),
            ],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur métier (publication impossible)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 400),
                new OA\Property(property: 'detail', type: 'string', example: 'La réglementation ne peut pas être publiée.'),
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
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
            $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));
        } catch (RegulationOrderRecordCannotBePublishedException) {
            return new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'detail' => 'L\'arrêté ne peut pas être publié.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'identifier' => $regulationOrderRecord->getRegulationOrder()->getIdentifier(),
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
        ], Response::HTTP_OK);
    }
}
