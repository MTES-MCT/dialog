<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class GeometryFormatter
{
    /**
     * @param Coordinates[] $points
     */
    public function formatLine(array $points): string
    {
        $coords = [];

        foreach ($points as $point) {
            $coords[] = sprintf('%.6f %.6f', $point->longitude, $point->latitude);
        }

        return sprintf('LINESTRING(%s)', implode(',', $coords));
    }
}
