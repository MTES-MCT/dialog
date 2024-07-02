<?php

declare(strict_types=1);

namespace App\Application\JOP\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Domain\Regulation\RegulationOrderRecord;

final class ImportJOPRegulationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ImportJOPRegulationCommand $command): void
    {
        /** @var RegulationOrderRecord */
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfoCommand);

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();

        foreach ($command->measureCommands as $measureCommand) {
            $measureCommand->regulationOrder = $regulationOrder;
            $measure = $this->commandBus->handle($measureCommand);
            $regulationOrder->addMeasure($measure);
        }

        $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
    }
}
