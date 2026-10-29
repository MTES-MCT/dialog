<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface LineSectionMakerInterface
{
    /**
     * Découpe $geometry entre les points $fromCoords (A) et $toCoords (B).
     *
     * La section retournée est orientée de A vers B : son premier sommet est le plus proche de A,
     * son dernier le plus proche de B. Les morceaux connectés sont fusionnés en une seule ligne.
     */
    public function computeSection(
        string $geometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
    ): string;
}
