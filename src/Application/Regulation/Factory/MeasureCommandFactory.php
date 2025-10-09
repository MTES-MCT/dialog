<?php

declare(strict_types=1);

namespace App\Application\Regulation\Factory;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\RegulationOrder;
use App\Infrastructure\DTO\Event\SaveMeasureDTO;

final class MeasureCommandFactory
{
    public function __construct(
        private VehicleSetCommandFactory $vehicleSetFactory,
        private PeriodCommandFactory $periodFactory,
        private LocationCommandFactory $locationFactory,
    ) {
    }

    public function fromDto(SaveMeasureDTO $dto, RegulationOrder $regulationOrder): SaveMeasureCommand
    {
        $cmd = SaveMeasureCommand::create($regulationOrder);
        $cmd->type = $dto->type?->value;
        $cmd->maxSpeed = $dto->maxSpeed;
        if ($dto->createdAt) {
            try {
                $cmd->createdAt = new \DateTimeImmutable($dto->createdAt);
            } catch (\Throwable) {
            }
        }

        $cmd->vehicleSet = $this->vehicleSetFactory->fromDto($dto->vehicleSet);

        if ($dto->periods) {
            foreach ($dto->periods as $periodDto) {
                $cmd->periods[] = $this->periodFactory->fromDto($periodDto);
            }
        }

        if ($dto->locations) {
            foreach ($dto->locations as $locationDto) {
                $cmd->addLocation($this->locationFactory->fromDto($locationDto));
            }
        }

        return $cmd;
    }
}
