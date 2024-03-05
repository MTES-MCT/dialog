<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeometryServiceInterface
{
    public function locatePointOnLine(string $line, Coordinates $point): float;

    public function clipLine(string $line, float $startFraction, float $endFraction): string;

    public function getFirstPointOfLinestring(string $line): Coordinates;
}
