<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Geography\GeoJSON;
use App\Domain\Geography\GMLv3;

final class DatexLocationView
{
    public readonly string $gmlPosList;

    public function __construct(
        public readonly string $roadName,
        public readonly string $geometry,
    ) {
        $this->gmlPosList = GMLv3::toPosList(GeoJSON::parseLineString($geometry));
    }
}
