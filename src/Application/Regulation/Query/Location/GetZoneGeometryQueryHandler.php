<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;

final class GetZoneGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetZoneGeometryQuery $query): string
    {
        // Périmètre et exceptions inchangés : on réutilise les tronçons déjà calculés.
        $zone = $query->location?->getZone();
        if ($zone && $query->location->getGeometry()
            && $zone->getGeometry() === $query->command->geometry
            && ExceptionsSignature::ofCommands($query->command->exceptions) === ExceptionsSignature::ofLocation($query->location)) {
            return $query->location->getGeometry();
        }

        // La restriction s'applique aux rues couvertes par le périmètre : on convertit le
        // polygone en tronçons de route (hors autoroutes), affichés dans l'aperçu et la carte
        // et exploitables par les exports linéaires (DATEX II, CIFS). clipToArea découpe les
        // tronçons à la frontière du polygone : seule la portion à l'intérieur est retenue.
        $sections = $this->roadGeocoder->findSectionsInArea(
            $query->command->geometry,
            excludeTypes: [$this->roadGeocoder::HIGHWAY],
            clipToArea: true,
        );

        if (!$query->command->exceptions) {
            return $sections;
        }

        // Toutes les exceptions (voies entières comprises) sont soustraites géométriquement
        // des tronçons : contrairement à « Ville entière », il n'y a pas d'exclusion par
        // identifiant BAN ici.
        $subtractGeometries = [];
        foreach ($query->command->exceptions as $exception) {
            $geometryQuery = $exception->getGeometryQuery();
            if ($geometryQuery) {
                $subtractGeometries[] = $this->queryBus->handle($geometryQuery);
            }
        }

        return $this->roadGeocoder->subtractGeometries(
            $sections,
            array_values(array_filter($subtractGeometries)),
        );
    }
}
