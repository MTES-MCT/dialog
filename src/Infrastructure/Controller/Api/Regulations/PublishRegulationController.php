<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
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
use Symfony\Component\Routing\Requirement\Requirement;

final class PublishRegulationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/regulations/{uuid}/publish',
        name: 'api_regulations_publish',
        methods: ['PUT'],
        requirements: ['uuid' => Requirement::UUID],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Response(
        response: 200,
        description: 'Arrêté publié',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: 'e413a47e-5928-4353-a8b2-8b7dda27f9a5'),
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
        response: 403,
        description: 'Organisation non autorisée à modifier cet arrêté',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 403),
                new OA\Property(property: 'detail', type: 'string', example: 'Forbidden'),
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
    public function __invoke(string $uuid): JsonResponse
    {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();

        try {
            /** @var RegulationOrderRecord $regulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            return new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => 'Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($regulationOrderRecord->getOrganizationUuid() !== $user->getOrganization()->getUuid()) {
            return new JsonResponse([
                'status' => Response::HTTP_FORBIDDEN,
                'detail' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
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
            'uuid' => $regulationOrderRecord->getUuid(),
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
        ], Response::HTTP_OK);
    }
}
