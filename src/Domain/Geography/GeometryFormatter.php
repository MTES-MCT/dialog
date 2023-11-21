<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class GeometryFormatter
{
    public function formatLine(float $fromLongitude, float $fromLatitude, float $toLongitude, float $toLatitude): string
    {
        return sprintf('LINESTRING(%.6f %.6f, %.6f %.6f)', $fromLongitude, $fromLatitude, $toLongitude, $toLatitude);
    }
}
