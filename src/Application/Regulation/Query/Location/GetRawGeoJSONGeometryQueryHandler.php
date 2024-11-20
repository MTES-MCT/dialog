<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Infrastructure\Adapter\GeoJSONGeometryConverter;

final class GetRawGeoJSONGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private GeoJSONGeometryConverter $converter,
    ) {
    }

    public function __invoke(GetRawGeoJSONGeometryQuery $query): string
    {
        return $this->converter->convertToGeometry($query->geometry);
    }
}
