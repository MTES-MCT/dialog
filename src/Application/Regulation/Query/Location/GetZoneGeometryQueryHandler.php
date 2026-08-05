<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;

final class GetZoneGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(GetZoneGeometryQuery $query): string
    {
        // Périmètre inchangé : on réutilise les tronçons déjà calculés.
        $zone = $query->location?->getZone();
        if ($zone && $query->location->getGeometry() && $zone->getGeometry() === $query->command->geometry) {
            return $query->location->getGeometry();
        }

        // La restriction s'applique aux rues couvertes par le périmètre : on convertit le
        // polygone en tronçons de route (hors autoroutes), affichés dans l'aperçu et la carte
        // et exploitables par les exports linéaires (DATEX II, CIFS). clipToArea découpe les
        // tronçons à la frontière du polygone : seule la portion à l'intérieur est retenue.
        return $this->roadGeocoder->findSectionsInArea(
            $query->command->geometry,
            excludeTypes: [$this->roadGeocoder::HIGHWAY],
            clipToArea: true,
        );
    }
}
