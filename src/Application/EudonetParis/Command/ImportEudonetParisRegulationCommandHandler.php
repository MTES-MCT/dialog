<?php

declare(strict_types=1);

namespace App\Application\EudonetParis\Command;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\RegulationOrderRecord;

final class ImportEudonetParisRegulationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ImportEudonetParisRegulationCommand $command): void
    {
        /** @var RegulationOrderRecord */
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfoCommand);

        foreach ($command->locationItems as $locationItem) {
            $locationCommand = new SaveRegulationLocationCommand($regulationOrderRecord);

            $locationCommand->cityCode = $command::CITY_CODE;
            $locationCommand->cityLabel = $command::CITY_LABEL;
            $locationCommand->roadName = $locationItem->roadName;
            $locationCommand->fromHouseNumber = $locationItem->fromHouseNumber;
            $locationCommand->toHouseNumber = $locationItem->toHouseNumber;
            $locationCommand->geometry = $locationItem->geometry;
            $locationCommand->measures = $locationItem->measures;

            $location = $this->commandBus->handle($locationCommand);

            $regulationOrderRecord->getRegulationOrder()->addLocation($location);
        }

        try {
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
        } catch (RegulationOrderRecordCannotBePublishedException $exc) {
            throw new ImportEudonetParisRegulationFailedException($exc->getMessage());
        }
    }
}
