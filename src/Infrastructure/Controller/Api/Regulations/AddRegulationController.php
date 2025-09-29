<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddRegulationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Security $security,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route(
        '/api/regulations',
        name: 'api_regulations_add',
        methods: ['POST'],
    )]
    #[OA\Tag(name: 'Regulations')]
    public function __invoke(#[MapRequestPayload] RegulationGeneralInfoDTO $dto): JsonResponse
    {
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

        return new JsonResponse(
            ['message' => \sprintf('Regulation %s created', $regulationOrderRecord->getUuid())],
            Response::HTTP_CREATED,
        );
    }
}
