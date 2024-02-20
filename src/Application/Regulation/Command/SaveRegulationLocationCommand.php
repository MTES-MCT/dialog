<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
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
    public ?bool $isEntireStreet = null;
    public ?string $fromHouseNumber = null;
    public ?string $toHouseNumber = null;
    public ?string $geometry;

    /** @var SaveMeasureCommand[] */
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
        $command->administrator = $location?->getAdministrator();
        $command->roadNumber = $location?->getRoadNumber();
        $command->isEntireStreet = $location ? $location->getIsEntireStreet() : true;
        $command->cityCode = $location?->getCityCode();
        $command->cityLabel = $location?->getCityLabel();
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
        if ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            $this->cityLabel = null;
            $this->cityCode = null;
            $this->roadName = null;
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }

        if ($this->roadType === RoadTypeEnum::LANE->value || $this->roadType === null) {
            $this->administrator = null;
            $this->roadNumber = null;
        }

        if ($this->roadType === RoadTypeEnum::LANE->value && $this->isEntireStreet) {
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }
    }
}
