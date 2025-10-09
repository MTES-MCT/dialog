<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

final class SaveRawGeoJSONDTO
{
    public ?string $label = null;
    public ?string $geometry = null; // GeoJSON string
}
