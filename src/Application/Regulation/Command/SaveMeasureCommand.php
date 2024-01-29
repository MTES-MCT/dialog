<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Location\SaveLocationNewCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?int $maxSpeed = null;
    public ?Location $location;
    /** @var SaveLocationNewCommand[] */
    public array $locationsNew = [];
    public array $periods = [];
    public ?\DateTimeInterface $createdAt = null;
    public ?SaveVehicleSetCommand $vehicleSet = null;

    public function __construct(
        public readonly ?Measure $measure = null,
    ) {
        $this->location = $measure?->getLocation();
        $this->type = $measure?->getType();
        $this->createdAt = $measure?->getCreatedAt();
        $this->maxSpeed = $measure?->getMaxSpeed();

        if ($measure) {
            $vehicleSet = $measure->getVehicleSet();

            if ($vehicleSet) {
                $this->vehicleSet = new SaveVehicleSetCommand($vehicleSet);
            }

            foreach ($measure->getLocationsNew() as $locationNew) {
                $this->locationsNew[] = new SaveLocationNewCommand($locationNew);
            }

            foreach ($measure->getPeriods() as $period) {
                $this->periods[] = new SavePeriodCommand($period);
            }
        }
    }
}
