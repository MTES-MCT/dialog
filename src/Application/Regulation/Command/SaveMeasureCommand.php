<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?Location $location;
    public array $periods = [];
    public ?\DateTimeInterface $createdAt = null;
    public ?SaveVehicleSetCommand $vehicleSet = null;

    public function __construct(
        public readonly ?Measure $measure = null,
    ) {
        $this->location = $measure?->getLocation();
        $this->type = $measure?->getType();
        $this->createdAt = $measure?->getCreatedAt();

        if ($measure) {
            if ($vehicleSet = $measure->getVehicleSet()) {
                $this->vehicleSet = new SaveVehicleSetCommand($vehicleSet);
            }

            foreach ($measure->getPeriods() as $period) {
                $this->periods[] = new SavePeriodCommand($period);
            }
        }
    }
}
