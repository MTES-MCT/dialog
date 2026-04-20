<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\RoadGeocoderInterface;

final class GetNearbyStreetsQueryHandler
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(GetNearbyStreetsQuery $query): array
    {
        return $this->roadGeocoder->findNearbyStreets(
            $query->geometry,
            $query->radius,
            $query->limit,
        );
    }
}
