<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use App\Infrastructure\Security\AuthenticatedUser;

final class PublishRegulationCommandHandler
{
    public function __construct(
        private CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
        private CommandBusInterface $commandBus,
        private AuthenticatedUser $authenticatedUser,
    ) {
    }

    public function __invoke(PublishRegulationCommand $command): void
    {
        if (false === $this->canRegulationOrderRecordBePublished->isSatisfiedBy($command->regulationOrderRecord)) {
            throw new RegulationOrderRecordCannotBePublishedException();
        }

        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();
        $user = $this->authenticatedUser->getUser();
        $action = ActionTypeEnum::PUBLISH->value;

        $this->commandBus->handle(new CreateRegulationOrderHistoryCommand($regulationOrder, $user, $action));

        $command->regulationOrderRecord->updateStatus(RegulationOrderRecordStatusEnum::PUBLISHED->value);
    }
}
