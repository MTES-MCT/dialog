<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class GMLv3
{
    /**
     * @param Coordinates[] $points
     */
    public static function toPosList(array $points): string
    {
        $coords = [];

        foreach ($points as $point) {
            $coords[] = $point->longitude;
            $coords[] = $point->latitude;
        }

        return implode(' ', $coords);
    }
}
