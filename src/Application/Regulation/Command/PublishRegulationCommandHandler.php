<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;

final class PublishRegulationCommandHandler
{
    public function __construct(
        private CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(PublishRegulationCommand $command): void
    {
        if (false === $this->canRegulationOrderRecordBePublished->isSatisfiedBy($command->regulationOrderRecord)) {
            throw new RegulationOrderRecordCannotBePublishedException();
        }

        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();

        $this->commandBus->handle(new CreateRegulationOrderHistoryCommand($regulationOrder, ActionTypeEnum::PUBLISH->value));

        $command->regulationOrderRecord->updateStatus(RegulationOrderRecordStatusEnum::PUBLISHED->value);
    }
}
