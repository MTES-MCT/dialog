<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final class SaveLocationDTO
{
    public ?RoadTypeEnum $roadType = null; // RoadTypeEnum
    public ?SaveNamedStreetDTO $namedStreet = null;
    public ?SaveNumberedRoadDTO $departmentalRoad = null;
    public ?SaveNumberedRoadDTO $nationalRoad = null;
    public ?SaveRawGeoJSONDTO $rawGeoJSON = null;
}
