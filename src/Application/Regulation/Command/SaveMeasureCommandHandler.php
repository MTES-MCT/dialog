<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class SaveMeasureCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private MeasureRepositoryInterface $measureRepository,
    ) {
    }

    public function __invoke(SaveMeasureCommand $command): Measure
    {
        if ($command->measure) {
            $command->measure->update($command->type);

            return $command->measure;
        }

        return $this->measureRepository->add(
            new Measure(
                uuid: $this->idFactory->make(),
                location: $command->location,
                type: $command->type,
            ),
        );
    }
}
