<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Regulation\Enum\DirectionEnum;

final class SaveNamedStreetDTO
{
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadBanId = null;
    public ?string $roadName = null;
    public ?string $fromPointType = null;
    public ?string $fromHouseNumber = null;
    public ?string $fromRoadBanId = null;
    public ?string $fromRoadName = null;
    public ?string $toPointType = null;
    public ?string $toHouseNumber = null;
    public ?string $toRoadBanId = null;
    public ?string $toRoadName = null;
    public ?string $geometry = null;
    public ?DirectionEnum $direction = null; // DirectionEnum
}
