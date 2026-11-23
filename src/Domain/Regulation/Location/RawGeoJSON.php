<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

use App\Domain\Regulation\Enum\DirectionEnum;

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
        // Sens de circulation concerné, relatif au tracé dessiné : A = premier point, B = dernier point.
        private string $direction = DirectionEnum::BOTH->value,
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

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function update(
        string $label,
        ?string $geometry = null,
        string $direction = DirectionEnum::BOTH->value,
    ): void {
        $this->label = $label;
        $this->geometry = $geometry;
        $this->direction = $direction;
    }
}
