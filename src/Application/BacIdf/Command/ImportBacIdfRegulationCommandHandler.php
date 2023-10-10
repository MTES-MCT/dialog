<?php

declare(strict_types=1);

namespace App\Application\BacIdf\Command;

use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\RegulationOrderRecord;

final class ImportBacIdfRegulationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ImportBacIdfRegulationCommand $command): void
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
            throw new ImportBacIdfRegulationFailedException($exc->getMessage());
        }
    }
}
