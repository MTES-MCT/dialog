<?php

declare(strict_types=1);

namespace App\Application\Cifs;

interface PolylineMakerInterface
{
    public function getPolylines(
        string $geometry,
        ?string $roadGeometry = null,
        int $pointBufferSize = 35,
    ): array;
}
