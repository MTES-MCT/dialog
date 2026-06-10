<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\DTO\Regulation\RegulationApiView;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GetRegulationController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    #[Route(
        '/api/regulations/{identifier}',
        name: 'api_regulations_get',
        methods: ['GET'],
        requirements: ['identifier' => '.+'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Response(
        response: 200,
        description: 'Arrêté récupéré',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', example: 'F2025/001'),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
                new OA\Property(property: 'category', type: 'string', example: 'temporaryRegulation'),
                new OA\Property(property: 'subject', type: 'string', nullable: true, example: 'roadMaintenance'),
                new OA\Property(property: 'otherCategoryText', type: 'string', nullable: true, example: null),
                new OA\Property(property: 'title', type: 'string', example: 'Travaux de voirie rue Exemple'),
                new OA\Property(property: 'startDate', type: 'string', format: 'date-time', nullable: true, example: '2025-10-09T08:00:00+00:00'),
                new OA\Property(property: 'endDate', type: 'string', format: 'date-time', nullable: true, example: '2025-10-15T18:00:00+00:00'),
                new OA\Property(
                    property: 'organization',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'uuid', type: 'string', nullable: true, example: '123e4567-e89b-12d3-a456-426614174000'),
                        new OA\Property(property: 'name', type: 'string', example: 'Ma collectivité'),
                    ],
                ),
                new OA\Property(
                    property: 'measures',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'uuid', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                            new OA\Property(property: 'type', type: 'string', example: 'speedLimitation'),
                            new OA\Property(property: 'maxSpeed', type: 'integer', nullable: true, example: 30),
                            new OA\Property(property: 'vehicleSet', type: 'object', nullable: true),
                            new OA\Property(property: 'periods', type: 'array', items: new OA\Items(type: 'object')),
                            new OA\Property(property: 'locations', type: 'array', items: new OA\Items(type: 'object')),
                        ],
                    ),
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

        /** @var GeneralInfoView $generalInfo */
        $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($regulationOrderRecord->getUuid()));

        /** @var MeasureView[] $measures */
        $measures = $this->queryBus->handle(new GetMeasuresQuery($regulationOrderRecord->getUuid()));

        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        return new JsonResponse(
            $this->normalizer->normalize(
                RegulationApiView::fromViews($generalInfo, $measures),
                'json',
                [DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM],
            ),
            Response::HTTP_OK,
        );
    }
}
