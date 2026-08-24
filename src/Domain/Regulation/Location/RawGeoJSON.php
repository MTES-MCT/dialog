<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class RawGeoJSON
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $label,
        // Tracé dessiné par l'utilisateur (GeoJSON), conservé pour la ré-édition.
        // La géométrie de la localisation contient quant à elle le tracé après
        // soustraction des exceptions (affiché dans l'aperçu, la carte et les exports).
        // Nullable pour les tracés créés avant l'introduction des exceptions.
        private ?string $geometry = null,
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

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function update(
        string $label,
        ?string $geometry = null,
    ): void {
        $this->label = $label;
        $this->geometry = $geometry;
    }
}
