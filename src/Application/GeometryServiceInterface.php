<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeometryServiceInterface
{
    public function locatePointOnLine(Coordinates $point, string $geometry): Coordinates;

    public function clipLine(string $lineGeometry, Coordinates $start, Coordinates $end): string;
}
