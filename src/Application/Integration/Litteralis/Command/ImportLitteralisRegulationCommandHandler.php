<?php

declare(strict_types=1);

namespace App\Application\Integration\Litteralis\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use App\Domain\Regulation\RegulationOrderRecord;

final class ImportLitteralisRegulationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ImportLitteralisRegulationCommand $command): void
    {
        /** @var RegulationOrderRecord */
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfoCommand);

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();

        foreach ($command->measureCommands as $measureCommand) {
            $measureCommand->regulationOrder = $regulationOrder;
            $measure = $this->commandBus->handle($measureCommand);
            $regulationOrder->addMeasure($measure);
        }

        // Créer un StorageRegulationOrder avec l'URL de téléchargement Sogelink si présente
        if ($command->downloadUrl !== null) {
            $storageCommand = new SaveRegulationOrderStorageCommand($regulationOrder);
            $storageCommand->url = $command->downloadUrl;
            $storageCommand->title = 'dl.sogelink.fr';
            $this->commandBus->handle($storageCommand);
        }

        $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
    }
}
