<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Infrastructure\DTO\Event\RegulationGeneralInfoDTO;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

final class AddRegulationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/api/regulations',
        name: 'api_regulations_add',
        methods: ['POST'],
    )]
    #[OA\Tag(name: 'Regulations')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', maxLength: 60, example: 'F2025/001'),
                new OA\Property(property: 'category', type: 'string', enum: ['temporaryRegulation', 'permanentRegulation'], example: 'temporaryRegulation'),
                new OA\Property(property: 'subject', type: 'string', enum: ['roadMaintenance', 'incident', 'event', 'winterMaintenance', 'other'], nullable: true, example: 'roadMaintenance'),
                new OA\Property(property: 'otherCategoryText', type: 'string', nullable: true, maxLength: 100, example: null),
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Travaux de voirie rue Exemple'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Création réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Regulation 123e4567-e89b-12d3-a456-426614174000 created'),
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
        description: 'Erreur de validation',
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
                            new OA\Property(property: 'propertyPath', type: 'string', example: 'title'),
                            new OA\Property(property: 'title', type: 'string', example: 'Cette valeur ne doit pas être vide.'),
                            new OA\Property(property: 'parameters', type: 'object'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    public function __invoke(
        #[MapRequestPayload] RegulationGeneralInfoDTO $dto,
    ): JsonResponse {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();

        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = $dto->identifier;
        $command->category = $dto->category;
        $command->subject = $dto->subject;
        $command->otherCategoryText = $dto->otherCategoryText;
        $command->title = $dto->title;
        $command->source = RegulationOrderRecordSourceEnum::API->value;
        $command->organization = $user->getOrganization();

        $regulationOrderRecord = $this->commandBus->handle($command);
        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        return new JsonResponse(
            ['message' => \sprintf('Regulation %s created', $regulationOrderRecord->getUuid())],
            Response::HTTP_CREATED,
        );
    }
}
