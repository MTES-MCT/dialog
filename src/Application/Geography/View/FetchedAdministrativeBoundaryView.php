<?php

declare(strict_types=1);

namespace App\Application\Geography\View;

final readonly class FetchedAdministrativeBoundaryView
{
    /**
     * @param string $geometry Contour au format GeoJSON (EPSG:4326)
     */
    public function __construct(
        public string $name,
        public string $geometry,
    ) {
    }
}
