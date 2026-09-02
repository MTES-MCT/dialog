<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;

final class GetWholeCityGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private QueryBusInterface $queryBus,
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

        $command = $query->command;

        // Les exceptions « voie entière » sont exclues exactement par leur identifiant BAN ;
        // les tronçons et tracés libres sont soustraits géométriquement (géométrie calculée via
        // les requêtes de géométrie habituelles).
        $subtractGeometries = [];
        foreach ($command->exceptions as $exception) {
            if ($exception->getExcludedRoadBanId() !== null) {
                continue;
            }

            $geometryQuery = $exception->getGeometryQuery();
            if ($geometryQuery) {
                $subtractGeometries[] = $this->queryBus->handle($geometryQuery);
            }
        }

        return $this->roadGeocoder->computeCityGeometry(
            $command->cityCode,
            $command->getExcludedRoadBanIds(),
            array_values(array_filter($subtractGeometries)),
        );
    }

    private function shouldRecomputeGeometry(GetWholeCityGeometryQuery $query): bool
    {
        $location = $query->location;

        if (!$location) {
            return true;
        }

        if ($query->command->cityCode !== $location->getCityCode()) {
            return true;
        }

        return ExceptionsSignature::ofCommands($query->command->exceptions) !== ExceptionsSignature::ofLocation($location);
    }
}
