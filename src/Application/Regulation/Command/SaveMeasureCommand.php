<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?int $maxSpeed = null;
    /** @var SaveLocationCommand[] */
    public array $locations = [];
    public array $periods = [];
    public ?\DateTimeInterface $createdAt = null;
    public ?SaveVehicleSetCommand $vehicleSet = null;
    public array $permissions = []; // For validation

    public function __construct(
        public ?RegulationOrder $regulationOrder = null,
        public readonly ?Measure $measure = null,
    ) {
    }

    public static function create(
        ?RegulationOrder $regulationOrder,
        ?Measure $measure = null,
    ): self {
        $command = new self($regulationOrder, $measure);

        $command->type = $measure?->getType();
        $command->createdAt = $measure?->getCreatedAt();
        $command->maxSpeed = $measure?->getMaxSpeed();

        if ($measure) {
            $vehicleSet = $measure->getVehicleSet();

            if ($vehicleSet) {
                $command->vehicleSet = new SaveVehicleSetCommand($vehicleSet);
            }

            foreach ($measure->getLocations() as $location) {
                $command->addLocation(new SaveLocationCommand($location));
            }

            foreach ($measure->getPeriods() as $period) {
                $command->periods[] = new SavePeriodCommand($period);
            }
        } else {
            $command->addLocation(new SaveLocationCommand());
            $command->periods[] = new SavePeriodCommand();
        }

        return $command;
    }

    public function addLocation(SaveLocationCommand $locationCommand): void
    {
        $locationCommand->permissions = &$this->permissions;
        $this->locations[] = $locationCommand;
    }
}
