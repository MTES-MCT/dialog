<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeometryServiceInterface
{
    public function locatePointOnLine(string $line, Coordinates $point): float;

    public function getFirstPointOfLinestring(string $line): Coordinates;

    public function clipLine(string $line, float $startFraction = 0, float $endFraction = 1): string;
}
