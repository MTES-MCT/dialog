<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class Zone
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $label,
        // Périmètre dessiné par l'utilisateur (polygone GeoJSON), conservé pour la ré-édition.
        // La géométrie de la localisation contient quant à elle les tronçons de rues couverts
        // par la zone (affichés dans l'aperçu, la carte et les exports).
        private string $geometry,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getGeometry(): string
    {
        return $this->geometry;
    }

    public function update(string $label, string $geometry): void
    {
        $this->label = $label;
        $this->geometry = $geometry;
    }
}
