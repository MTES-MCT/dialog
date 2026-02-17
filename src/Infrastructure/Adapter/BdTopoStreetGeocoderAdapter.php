<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Domain\Geography\Coordinates;

/**
 * Géocodeur composite : voies via BD Topo, villes et coordonnées via API Adresse.
 */
final class BdTopoStreetGeocoderAdapter implements GeocoderInterface
{
    public function __construct(
        private APIAdresseGeocoder $apiAdresseGeocoder,
        private BdTopoRoadGeocoder $bdTopoRoadGeocoder,
    ) {
    }

    public function computeCoordinates(string $address, string $cityCode): Coordinates
    {
        return $this->apiAdresseGeocoder->computeCoordinates($address, $cityCode);
    }

    public function findCities(string $search): array
    {
        return $this->apiAdresseGeocoder->findCities($search);
    }

    public function findNamedStreets(string $search, string $cityCode): array
    {
        return $this->bdTopoRoadGeocoder->findNamedStreets($search, $cityCode);
    }

    /**
     * @throws GeocodingFailureException
     */
    public function getRoadBanId(string $search, string $cityCode): string
    {
        $namedStreets = $this->bdTopoRoadGeocoder->findNamedStreets($search, $cityCode);

        if (empty($namedStreets)) {
            throw new GeocodingFailureException(\sprintf("no named street found for search='%s' and cityCode='%s'", $search, $cityCode));
        }

        return $namedStreets[0]['roadBanId'];
    }
}
