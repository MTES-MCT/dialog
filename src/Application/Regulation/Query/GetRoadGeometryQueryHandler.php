<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\RoadGeocoderInterface;

final class GetRoadGeometryQueryHandler
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(GetRoadGeometryQuery $query): string
    {
        return $this->roadGeocoder->computeRoadLine($query->roadName, $query->cityCode);
    }
}
