<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Regulation\Enum\MeasureTypeEnum;

final class SaveMeasureDTO
{
    public ?MeasureTypeEnum $type = null;
    public ?int $maxSpeed = null;
    public ?string $createdAt = null; // ISO 8601
    /** @var SavePeriodDTO[]|null */
    public ?array $periods = null;
    /** @var SaveLocationDTO[]|null */
    public ?array $locations = null;
    public ?SaveVehicleSetDTO $vehicleSet = null;
}
