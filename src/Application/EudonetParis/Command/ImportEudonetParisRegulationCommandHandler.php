<?php

declare(strict_types=1);

namespace App\Application\EudonetParis\Command;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\Regulation\Command\PublishRegulationCommand;
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

        foreach ($command->locationCommands as $locationCommand) {
            $locationCommand->regulationOrderRecord = $regulationOrderRecord;

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
