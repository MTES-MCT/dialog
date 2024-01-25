<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\LocationTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationLocationCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadName = null;
    public ?string $fromHouseNumber = null;
    public ?string $toHouseNumber = null;
    public ?string $geometry;

    public array $measures = [];

    public function __construct(
        public ?RegulationOrderRecord $regulationOrderRecord = null,
        public readonly ?Location $location = null,
    ) {
    }

    public static function create(
        RegulationOrderRecord $regulationOrderRecord,
        Location $location = null,
    ): self {
        $command = new self($regulationOrderRecord, $location);
        $command->roadType = $location?->getRoadType();
        $command->cityCode = $location?->getCityCode();
        $command->cityLabel = $location?->getCityLabel();
        $command->administrator = $location?->getAdministrator();
        $command->roadNumber = $location?->getRoadNumber();
        $command->roadName = $location?->getRoadName();
        $command->fromHouseNumber = $location?->getFromHouseNumber();
        $command->toHouseNumber = $location?->getToHouseNumber();

        if ($location) {
            foreach ($location->getMeasures() as $measure) {
                $command->measures[] = new SaveMeasureCommand($measure);
            }
        } else {
            $command->measures[] = new SaveMeasureCommand();
        }

        return $command;
    }

    public function clean(): void
    {
        if ($this->roadType == LocationTypeEnum::DEPARTMENTAL_ROAD->value) {
            $this->cityLabel = null;
            $this->cityCode = null;
            $this->roadName = null;
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }

        if ($this->roadType == LocationTypeEnum::LANE->value || $this->roadType == null) {
            $this->administrator = null;
            $this->roadNumber = null;
        }
    }
}
