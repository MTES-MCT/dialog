<?php

declare(strict_types=1);

namespace App\Application;

interface PointNumberSideDetectorInterface
{
    public function detect(
        string $direction,
        string $administrator,
        string $roadNumber,
        string $fromPointNumber,
        int $fromAbscissa,
        string $toPointNumber,
        int $toAbscissa,
    ): array;
}
