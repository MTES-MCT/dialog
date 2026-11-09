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

    /**
     * Retourne les polylines CIFS ("lat lon lat lon ...") de la géométrie : une par groupe de tronçons connectés,
     * les tronçons disjoints donnant chacun leur propre polyline.
     *
     * @return string[]
     */
    public function getPolylines(string $geometry): array;
}
