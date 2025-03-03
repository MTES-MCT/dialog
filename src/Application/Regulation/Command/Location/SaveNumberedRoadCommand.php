<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetNumberedRoadGeometryQuery;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\StorageArea;
use App\Domain\Regulation\Measure;

final class SaveNumberedRoadCommand implements RoadCommandInterface
{
    public ?string $roadType = null; // Used by validation
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $fromPointNumberValue = null;
    public ?string $fromPointNumber = null;
    public ?string $fromDepartmentCode = null;
    public ?int $fromAbscissa = null;
    public ?string $fromSide = null;
    public ?string $toPointNumberValue = null;
    public ?string $toPointNumber = null;
    public ?string $toDepartmentCode = null;
    public ?int $toAbscissa = null;
    public ?string $toSide = null;
    public string $direction = DirectionEnum::BOTH->value;
    public ?StorageArea $storageArea = null;
    public ?string $geometry = null;
    public ?Measure $measure;
    public ?Location $location = null;

    public function __construct(
        public ?NumberedRoad $numberedRoad = null,
    ) {
        $this->administrator = $numberedRoad?->getAdministrator();
        $this->roadNumber = $numberedRoad?->getRoadNumber();
        $this->fromPointNumber = $numberedRoad?->getFromPointNumber();
        $this->fromDepartmentCode = $numberedRoad?->getFromDepartmentCode();
        $this->fromPointNumberValue = self::encodePointNumberValue($this->fromPointNumber, $this->fromDepartmentCode);
        $this->fromSide = $numberedRoad?->getFromSide();
        $this->fromAbscissa = $numberedRoad?->getFromAbscissa();
        $this->toPointNumber = $numberedRoad?->getToPointNumber();
        $this->toDepartmentCode = $numberedRoad?->getToDepartmentCode();
        $this->toPointNumberValue = self::encodePointNumberValue($this->toPointNumber, $this->toDepartmentCode);
        $this->toAbscissa = $numberedRoad?->getToAbscissa();
        $this->toSide = $numberedRoad?->getToSide();
        $this->direction = $numberedRoad?->getDirection() ?? DirectionEnum::BOTH->value;
        $this->storageArea = $numberedRoad?->getLocation()?->getStorageArea();
        $this->roadType = $numberedRoad?->getLocation()?->getRoadType();
    }

    public static function encodePointNumberValue(?string $pointNumber, ?string $departmentCode): ?string
    {
        if (empty($pointNumber)) {
            return null;
        }

        if (empty($departmentCode)) {
            return $pointNumber;
        }

        return implode('##', [$pointNumber, $departmentCode]);
    }

    public static function decodePointNumberValue(string $value): array
    {
        // '122' -> ['122', null]
        // '122#22' -> ['122', '22']
        $parts = explode('##', $value, 2);

        return \count($parts) === 2 ? $parts : [$value, null];
    }

    // Road command interface

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getGeometryQuery(): QueryInterface
    {
        return new GetNumberedRoadGeometryQuery($this, $this->location, $this->geometry);
    }

    public function clean(): void
    {
        if ($this->roadType !== RoadTypeEnum::NATIONAL_ROAD->value) {
            $this->storageArea = null;
        }

        [$this->fromPointNumber, $this->fromDepartmentCode] = self::decodePointNumberValue($this->fromPointNumberValue);
        [$this->toPointNumber, $this->toDepartmentCode] = self::decodePointNumberValue($this->toPointNumberValue);
    }
}
