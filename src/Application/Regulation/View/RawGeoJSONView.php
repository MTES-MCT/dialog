<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final readonly class RawGeoJSONView implements LocationViewInterface
{
    public string $roadType;

    public function __construct(
        public string $label,
    ) {
        $this->roadType = RoadTypeEnum::RAW_GEOJSON->value;
    }
}
