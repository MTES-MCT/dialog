<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Measure;
use App\Domain\User\Organization;

final class SaveLocationCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?Measure $measure = null;
    public ?Organization $organization = null;
    public ?SaveNumberedRoadCommand $departmentalRoad = null;
    public ?SaveNumberedRoadCommand $nationalRoad = null;
    public ?SaveNamedStreetCommand $namedStreet = null;
    public ?SaveRawGeoJSONCommand $rawGeoJSON = null;
    public array $permissions = []; // For validation

    public function __construct(
        public readonly ?Location $location = null,
    ) {
        $this->roadType = $location?->getRoadType();

        if ($location) {
            $this->organization = $location->getMeasure()->getRegulationOrder()->getRegulationOrderRecord()->getOrganization();
        }

        if ($location?->getNamedStreet()) {
            $this->namedStreet = new SaveNamedStreetCommand($location->getNamedStreet());
        }

        if ($location?->getNumberedRoad()) {
            $numberedRoad = new SaveNumberedRoadCommand($location->getNumberedRoad());
            $numberedRoad->location = $location;
            $this->assignNumberedRoad($numberedRoad);
        }

        if ($location?->getRawGeoJSON()) {
            $this->rawGeoJSON = new SaveRawGeoJSONCommand($location->getRawGeoJSON());
        }
    }

    public function assignNumberedRoad(SaveNumberedRoadCommand $numberedRoad): void
    {
        if ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            $numberedRoad->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
            $this->departmentalRoad = $numberedRoad;
        } else {
            $numberedRoad->roadType = RoadTypeEnum::NATIONAL_ROAD->value;
            $this->nationalRoad = $numberedRoad;
        }
    }

    public function clean(): void
    {
        if ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            // /!\ (**) Il y a déjà une NumberedRoad en DB qui correspond à une départementale.
            // On recopie la référence vers la commande qui va la transformer en nationale.
            if ($numberedRoad = $this->nationalRoad?->numberedRoad) {
                $this->departmentalRoad->numberedRoad = $numberedRoad;
            }

            $this->namedStreet = null;
            $this->nationalRoad = null;
            $this->rawGeoJSON = null;
        }

        if ($this->roadType === RoadTypeEnum::NATIONAL_ROAD->value) {
            // /!\ Idem que (**) ci-dessus.
            if ($numberedRoad = $this->departmentalRoad?->numberedRoad) {
                $this->nationalRoad->numberedRoad = $numberedRoad;
            }

            $this->namedStreet = null;
            $this->departmentalRoad = null;
            $this->rawGeoJSON = null;
        }

        if ($this->roadType === RoadTypeEnum::LANE->value) {
            $this->departmentalRoad = null;
            $this->nationalRoad = null;
            $this->rawGeoJSON = null;
        }

        if ($this->roadType == RoadTypeEnum::RAW_GEOJSON->value) {
            $this->namedStreet = null;
            $this->departmentalRoad = null;
            $this->nationalRoad = null;
        }
    }

    public function getRoadCommand(): RoadCommandInterface
    {
        return match ($this->roadType) {
            RoadTypeEnum::LANE->value => $this->namedStreet,
            RoadTypeEnum::DEPARTMENTAL_ROAD->value => $this->departmentalRoad,
            RoadTypeEnum::NATIONAL_ROAD->value => $this->nationalRoad,
            RoadTypeEnum::RAW_GEOJSON->value => $this->rawGeoJSON,
            default => throw new \LogicException('No road command'),
        };
    }

    public function getRoadDeleteCommand(): ?CommandInterface
    {
        if (!$this->namedStreet && $namedStreet = $this->location->getNamedStreet()) {
            return new DeleteNamedStreetCommand($namedStreet);
        }

        if (!$this->departmentalRoad && !$this->nationalRoad && $numberedRoad = $this->location->getNumberedRoad()) {
            return new DeleteNumberedRoadCommand($numberedRoad);
        }

        if (!$this->rawGeoJSON && $rawGeoJSON = $this->location->getRawGeoJSON()) {
            return new DeleteRawGeoJSONCommand($rawGeoJSON);
        }

        return null;
    }
}
