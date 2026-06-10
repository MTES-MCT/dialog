<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\LocationView;

final readonly class LocationApiView
{
    public function __construct(
        public string $uuid,
        public string $roadType,
        public ?NamedStreetApiView $namedStreet,
        public ?NumberedRoadApiView $numberedRoad,
        public ?RawGeoJSONApiView $rawGeoJSON,
        public ?StorageAreaApiView $storageArea,
        public ?string $geometry,
    ) {
    }

    public static function fromView(LocationView $view): self
    {
        return new self(
            uuid: $view->uuid,
            roadType: $view->roadType,
            namedStreet: $view->namedStreet ? NamedStreetApiView::fromView($view->namedStreet) : null,
            numberedRoad: $view->numberedRoad ? NumberedRoadApiView::fromView($view->numberedRoad) : null,
            rawGeoJSON: $view->rawGeoJSON ? RawGeoJSONApiView::fromView($view->rawGeoJSON) : null,
            storageArea: $view->storageArea ? StorageAreaApiView::fromView($view->storageArea) : null,
            geometry: $view->geometry,
        );
    }
}
