<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Location\SaveLocationNewCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?int $maxSpeed = null;
    /** @var SaveLocationNewCommand[] */
    public array $locationsNew = [];
    public array $periods = [];
    public ?\DateTimeInterface $createdAt = null;
    public ?SaveVehicleSetCommand $vehicleSet = null;

    public function __construct(
        public ?RegulationOrder $regulationOrder = null,
        public readonly ?Measure $measure = null,
    ) {
    }

    public static function create(
        ?RegulationOrder $regulationOrder,
        Measure $measure = null,
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

            foreach ($measure->getLocationsNew() as $locationNew) {
                $command->locationsNew[] = new SaveLocationNewCommand($locationNew);
            }

            foreach ($measure->getPeriods() as $period) {
                $command->periods[] = new SavePeriodCommand($period);
            }
        } else {
            $command->locationsNew[] = new SaveLocationNewCommand();
        }

        return $command;
    }
}
