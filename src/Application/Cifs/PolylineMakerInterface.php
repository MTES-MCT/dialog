<?php

declare(strict_types=1);

namespace App\Application\Cifs;

interface PolylineMakerInterface
{
    /**
     * Convertit toute géométrie GeoJSON en LineString ou MultiLineString uniquement.
     * Extrait les parties de type ligne (ignore points, polygones), fusionne les segments connectés, retourne du GeoJSON.
     * Retourne null lorsque la géométrie n'a aucune partie de type ligne.
     */
    public function normalizeToLineStringGeoJSON(string $geometry): ?string;

    public function attemptMergeLines(string $geometry): ?string;

    public function getMergedPolyline(string $geometry): string;
}
