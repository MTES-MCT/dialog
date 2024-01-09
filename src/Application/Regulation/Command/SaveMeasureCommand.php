<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Location\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?int $maxSpeed = null;
    public array $periods = [];
    public array $locations = [];
    public ?\DateTimeInterface $createdAt = null;
    public ?SaveVehicleSetCommand $vehicleSet = null;

    public function __construct(
        public readonly RegulationOrder $regulationOrder,
        public readonly ?Measure $measure = null,
    ) {
        $this->type = $measure?->getType();
        $this->createdAt = $measure?->getCreatedAt();
        $this->maxSpeed = $measure?->getMaxSpeed();

        if ($measure) {
            $vehicleSet = $measure->getVehicleSet();

            if ($vehicleSet) {
                $this->vehicleSet = new SaveVehicleSetCommand($vehicleSet);
            }

            foreach ($measure->getLocations() as $location) {
                $this->locations[] = new SaveRegulationLocationCommand($location);
            }

            foreach ($measure->getPeriods() as $period) {
                $this->periods[] = new SavePeriodCommand($period);
            }
        }
    }
}
