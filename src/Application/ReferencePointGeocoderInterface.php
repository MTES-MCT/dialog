<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface ReferencePointGeocoderInterface
{
    public function compute(
        string $administrator,
        string $roadNumber,
        string $direction,
        int $pointNumber,
        ?int $abscissa,
    ): Coordinates;
}
