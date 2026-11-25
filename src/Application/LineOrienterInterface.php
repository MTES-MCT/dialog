<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface LineOrienterInterface
{
    /**
     * Oriente une géométrie linéaire (GeoJSON) du point A vers le point B.
     *
     * Les morceaux connectés sont fusionnés en une seule ligne ; le premier sommet du résultat est
     * le plus proche de A, le dernier le plus proche de B. Les morceaux non connectés sont chacun
     * orientés depuis A puis ordonnés par distance croissante à A. Une géométrie sans partie
     * linéaire (points) est renvoyée telle quelle.
     */
    public function orient(string $geometry, Coordinates $from, Coordinates $to): string;

    /**
     * Oriente une géométrie linéaire sans point A ni B connus (voie entière) de façon déterministe :
     * A est l'extrémité la plus à l'ouest (puis au sud), B l'extrémité la plus éloignée de A.
     * Le résultat ne dépend donc pas de l'ordre des tronçons en entrée, ce qui garantit qu'aperçu
     * et enregistrement placent A et B au même endroit.
     */
    public function normalize(string $geometry): string;
}
