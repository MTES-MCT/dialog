<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Measure;

final class SaveLocationCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?Measure $measure = null;
    public ?SaveNumberedRoadCommand $numberedRoad = null;
    public ?SaveNamedStreetCommand $namedStreet = null;
    public ?SaveRawGeoJSONCommand $rawGeoJSON = null;
    public array $permissions = []; // For validation

    public function __construct(
        public readonly ?Location $location = null,
    ) {
        $this->roadType = $location?->getRoadType();

        if ($location?->getNamedStreet()) {
            $this->namedStreet = new SaveNamedStreetCommand($location->getNamedStreet());
        }

        if ($location?->getNumberedRoad()) {
            $this->numberedRoad = new SaveNumberedRoadCommand($location->getNumberedRoad());
        }

        if ($location?->getRawGeoJSON()) {
            $this->rawGeoJSON = new SaveRawGeoJSONCommand($location->getRawGeoJSON());
        }
    }

    public function clean(): void
    {
        if ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            $this->namedStreet = null;
            $this->rawGeoJSON = null;
        }

        if ($this->roadType === RoadTypeEnum::LANE->value) {
            $this->numberedRoad = null;
            $this->rawGeoJSON = null;
        }

        if ($this->roadType == RoadTypeEnum::RAW_GEOJSON->value) {
            $this->namedStreet = null;
            $this->numberedRoad = null;
        }
    }

    public function getRoadCommand(): RoadCommandInterface
    {
        return $this->namedStreet ?? $this->numberedRoad ?? $this->rawGeoJSON ?? throw new \LogicException('No road command');
    }

    public function getRoadDeleteCommand(): ?CommandInterface
    {
        if (!$this->namedStreet && $namedStreet = $this->location->getNamedStreet()) {
            return new DeleteNamedStreetCommand($namedStreet);
        }

        if (!$this->numberedRoad && $numberedRoad = $this->location->getNumberedRoad()) {
            return new DeleteNumberedRoadCommand($numberedRoad);
        }

        if (!$this->rawGeoJSON && $rawGeoJSON = $this->location->getRawGeoJSON()) {
            return new DeleteRawGeoJSONCommand($rawGeoJSON);
        }

        return null;
    }
}
