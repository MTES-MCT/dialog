<?php

declare(strict_types=1);

namespace App\Application\EudonetParis\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\RegulationOrderRecord;

final class ImportEudonetParisRegulationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ImportEudonetParisRegulationCommand $command): void
    {
        $command->generalInfoCommand->source = RegulationOrderRecordSourceEnum::EUDONET_PARIS;

        /** @var RegulationOrderRecord */
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfoCommand);

        foreach ($command->locationItems as $locationItem) {
            $locationCommand = new SaveRegulationLocationCommand($regulationOrderRecord);

            $locationCommand->address = $locationItem->address;
            $locationCommand->fromHouseNumber = $locationItem->fromHouseNumber;
            $locationCommand->toHouseNumber = $locationItem->toHouseNumber;
            $locationCommand->fromPoint = $locationItem->fromPoint;
            $locationCommand->toPoint = $locationItem->toPoint;
            $locationCommand->measures = $locationItem->measures;

            $location = $this->commandBus->handle($locationCommand);

            $regulationOrderRecord->getRegulationOrder()->addLocation($location);
        }

        $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
    }
}
