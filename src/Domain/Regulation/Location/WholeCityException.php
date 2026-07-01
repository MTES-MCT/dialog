<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

/**
 * Une voie (entière ou tronçon) ou un tracé libre exclu d'une restriction « Ville entière ».
 *
 * Pas de sous-entité dédiée par type de voie : le descripteur structuré nécessaire à la
 * ré-édition du formulaire est conservé en JSON dans `data`, la géométrie calculée dans
 * `geometry` (pour la soustraction et l'affichage), et un résumé lisible dans `label`.
 */
class WholeCityException
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $roadType,
        private string $label,
        private ?string $geometry = null,
        private array $data = [],
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

    public function getRoadType(): string
    {
        return $this->roadType;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function update(string $roadType, string $label, ?string $geometry, array $data): void
    {
        $this->roadType = $roadType;
        $this->label = $label;
        $this->geometry = $geometry;
        $this->data = $data;
    }
}
