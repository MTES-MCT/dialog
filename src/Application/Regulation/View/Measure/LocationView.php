<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class LocationView
{
    public function __construct(
        public string $uuid,
        public string $roadType,
        public ?NamedStreetView $namedStreet = null,
        public ?NumberedRoadView $numberedRoad = null,
        public ?RawGeoJSONView $rawGeoJSON = null,
        public ?StorageAreaView $storageArea = null,
        public ?string $geometry = null,
        public ?WholeCityView $wholeCity = null,
        public ?ZoneView $zone = null,
    ) {
    }

    /**
     * Sens de circulation concerné (BOTH, A_TO_B ou B_TO_A), la géométrie étant orientée de A vers B.
     * Null pour les localisations sans sens (ville entière, zone).
     */
    public function getDirection(): ?string
    {
        return $this->namedStreet->direction
            ?? $this->numberedRoad->direction
            ?? $this->rawGeoJSON?->direction;
    }
}
