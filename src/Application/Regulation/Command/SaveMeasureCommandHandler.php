<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class SaveMeasureCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private MeasureRepositoryInterface $measureRepository,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(SaveMeasureCommand $command): Measure
    {
        if ($command->measure) {
            $command->measure->update($command->type);
            $this->handlePeriods($command, $command->measure);

            return $command->measure;
        }

        $measure = $this->measureRepository->add(
            new Measure(
                uuid: $this->idFactory->make(),
                location: $command->location,
                type: $command->type,
            ),
        );

        $this->handlePeriods($command, $measure);

        return $measure;
    }

    private function handlePeriods(SaveMeasureCommand $command, Measure $measure): void
    {
        foreach ($command->periods as $periodCommand) {
            $periodCommand->measure = $measure;
            $this->commandBus->handle($periodCommand);
        }
    }
}
