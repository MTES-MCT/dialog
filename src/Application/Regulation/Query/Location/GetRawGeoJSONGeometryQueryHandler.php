<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;

final class GetRawGeoJSONGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetRawGeoJSONGeometryQuery $query): string
    {
        $command = $query->command;

        if (!$command->exceptions) {
            return $command->geometry;
        }

        // Tracé et exceptions inchangés : on réutilise la géométrie déjà calculée.
        if ($query->location && !$this->shouldRecomputeGeometry($query)) {
            return $query->location->getGeometry();
        }

        // Toutes les exceptions (voies entières comprises) sont soustraites géométriquement
        // du tracé dessiné : contrairement à « Ville entière », il n'y a pas d'exclusion
        // par identifiant BAN ici.
        $subtractGeometries = [];
        foreach ($command->exceptions as $exception) {
            $geometryQuery = $exception->getGeometryQuery();
            if ($geometryQuery) {
                $subtractGeometries[] = $this->queryBus->handle($geometryQuery);
            }
        }

        return $this->roadGeocoder->subtractGeometries(
            $command->geometry,
            array_values(array_filter($subtractGeometries)),
        );
    }

    private function shouldRecomputeGeometry(GetRawGeoJSONGeometryQuery $query): bool
    {
        $location = $query->location;

        // Pas de tracé d'origine stocké (tracé antérieur aux exceptions) : on recalcule.
        if ($query->command->geometry !== $location->getRawGeoJSON()?->getGeometry()) {
            return true;
        }

        return ExceptionsSignature::ofCommands($query->command->exceptions) !== ExceptionsSignature::ofLocation($location);
    }
}
