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
    public ?string $fromDepartmentCode = null;
    public ?string $fromPointNumber = null;
    public ?int $fromAbscissa = null;
    public ?string $fromSide = null;
    public ?string $toDepartmentCode = null;
    public ?string $toPointNumber = null;
    public ?int $toAbscissa = null;
    public ?string $toSide = null;
    public string $direction = DirectionEnum::BOTH->value;
    public ?StorageArea $storageArea = null;
    public ?string $geometry = null;
    public ?Measure $measure;
    public ?Location $location = null;

    // FormType only
    public ?string $fromPointNumberWithDepartmentCode = null;
    public ?string $fromPointNumberWithDepartmentCodeLabel = null;
    public ?string $toPointNumberWithDepartmentCode = null;
    public ?string $toPointNumberWithDepartmentCodeLabel = null;

    public function __construct(
        public ?NumberedRoad $numberedRoad = null,
    ) {
        $this->administrator = $numberedRoad?->getAdministrator();
        $this->roadNumber = $numberedRoad?->getRoadNumber();
        $this->fromDepartmentCode = $numberedRoad?->getFromDepartmentCode();
        $this->fromPointNumber = $numberedRoad?->getFromPointNumber();
        $this->fromSide = $numberedRoad?->getFromSide();
        $this->fromAbscissa = $numberedRoad?->getFromAbscissa();
        $this->toDepartmentCode = $numberedRoad?->getToDepartmentCode();
        $this->toPointNumber = $numberedRoad?->getToPointNumber();
        $this->toAbscissa = $numberedRoad?->getToAbscissa();
        $this->toSide = $numberedRoad?->getToSide();
        $this->direction = $numberedRoad?->getDirection() ?? DirectionEnum::BOTH->value;
        $this->storageArea = $numberedRoad?->getLocation()?->getStorageArea();
        $this->roadType = $numberedRoad?->getLocation()?->getRoadType();

        $this->prepareReferencePoints();
    }

    public static function encodePointNumberWithDepartmentCode(?string $departmentCode, ?string $pointNumber): ?string
    {
        // WARNING (1): empty($pointNumber) ne convient pas car '0' est un PR valide mais empty('0') renvoie true en PHP.
        if ($pointNumber === null || $pointNumber === '') {
            return null;
        }

        if (empty($departmentCode)) {
            return $pointNumber;
        }

        return implode('##', [$departmentCode, $pointNumber]);
    }

    public static function decodePointNumberWithDepartmentCode(?string $value): array
    {
        // WARNING: idem que (1).
        if ($value === null || $value === '') {
            return [null, null];
        }

        // '122' -> [null, '122]
        // '122#22' -> ['22', '122']
        $parts = explode('##', $value, 2);

        if (\count($parts) === 2) {
            [$departmentCode, $pointNumber] = $parts;

            return [$departmentCode, $pointNumber];
        }

        $pointNumber = $value;

        return [null, $pointNumber];
    }

    public static function makePointNumberWithDepartmentCodeLabel(?string $departmentCode, ?string $pointNumber): ?string
    {
        // WARNING: idem que (1).
        if ($pointNumber === null || $pointNumber === '') {
            return null;
        }

        if (empty($departmentCode)) {
            return $pointNumber;
        }

        return \sprintf('%s (dép %s)', $pointNumber, $departmentCode);
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

    public function prepareReferencePoints(): void
    {
        $this->fromPointNumberWithDepartmentCode = self::encodePointNumberWithDepartmentCode($this->fromDepartmentCode, $this->fromPointNumber);
        $this->fromPointNumberWithDepartmentCodeLabel = self::makePointNumberWithDepartmentCodeLabel($this->fromDepartmentCode, $this->fromPointNumber);
        $this->toPointNumberWithDepartmentCode = self::encodePointNumberWithDepartmentCode($this->toDepartmentCode, $this->toPointNumber);
        $this->toPointNumberWithDepartmentCodeLabel = self::makePointNumberWithDepartmentCodeLabel($this->toDepartmentCode, $this->toPointNumber);
    }

    public function clean(): void
    {
        if ($this->roadType !== RoadTypeEnum::NATIONAL_ROAD->value) {
            $this->storageArea = null;
        }

        [$this->fromDepartmentCode, $this->fromPointNumber] = self::decodePointNumberWithDepartmentCode($this->fromPointNumberWithDepartmentCode);
        [$this->toDepartmentCode, $this->toPointNumber] = self::decodePointNumberWithDepartmentCode($this->toPointNumberWithDepartmentCode);
    }
}
