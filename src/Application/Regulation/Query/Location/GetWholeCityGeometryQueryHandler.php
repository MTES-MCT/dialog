<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;

final class GetWholeCityGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(GetWholeCityGeometryQuery $query): string
    {
        if ($query->geometry) {
            return $query->geometry;
        }

        if ($query->location && !$this->shouldRecomputeGeometry($query)) {
            return $query->location->getGeometry();
        }

        return $this->roadGeocoder->computeCityGeometry(
            $query->command->cityCode,
            $query->command->getExcludedRoadBanIds(),
        );
    }

    private function shouldRecomputeGeometry(GetWholeCityGeometryQuery $query): bool
    {
        $command = $query->command;
        $wholeCity = $command->wholeCity;

        if (!$wholeCity) {
            return true;
        }

        if ($command->cityCode !== $wholeCity->getCityCode()) {
            return true;
        }

        $persistedExcludedIds = [];
        foreach ($wholeCity->getExceptions() as $exception) {
            $persistedExcludedIds[] = $exception->getRoadBanId();
        }

        $newExcludedIds = $command->getExcludedRoadBanIds();

        sort($persistedExcludedIds);
        sort($newExcludedIds);

        return $persistedExcludedIds !== $newExcludedIds;
    }
}
