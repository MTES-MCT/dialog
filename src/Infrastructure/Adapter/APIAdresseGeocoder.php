<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Domain\Geography\Coordinates;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class APIAdresseGeocoder implements GeocoderInterface
{
    public function __construct(
        private HttpClientInterface $http,
    ) {
    }

    public function computeCoordinates(
        string $postalCode,
        string $city,
        string $road,
        string $houseNumber,
    ): Coordinates {
        // See: https://adresse.data.gouv.fr/api-doc/adresse
        $url = 'https://api-adresse.data.gouv.fr/search/';

        $q = sprintf('%s %s %s %s', $houseNumber, $road, $postalCode, $city);

        $response = $this->http->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'q' => $q,
                'limit' => 1,
                // Hints for the API
                'type' => 'housenumber',
                'postcode' => $postalCode,
            ],
        ]);

        // Check the response status...

        if ($response->getStatusCode() >= 500) {
            $message = sprintf('server error: HTTP %d', $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        if ($response->getStatusCode() >= 400) {
            $message = sprintf('client error: HTTP %d', $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        if ($response->getStatusCode() >= 300) {
            $message = sprintf('too many redirects: HTTP %d', $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        // Decode the data according to the GeoJSON FeatureCollection spec.
        // Fail fast if we encounter any unexpected format issue.

        try {
            $data = $response->toArray(false);
        } catch (DecodingExceptionInterface $exc) {
            $message = sprintf('invalid json: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        // We want $data['features'][0]['geometry']['coordinates'].

        if (!\array_key_exists('features', $data)) {
            throw new GeocodingFailureException('key error: features');
        }

        if (\count($data['features']) == 0) {
            $message = 'error: expected 1 result, got 0';
            throw new GeocodingFailureException($message);
        }

        $point = $data['features'][0];

        if (!\array_key_exists('geometry', $point)) {
            throw new GeocodingFailureException('key error: geometry');
        }

        if (!\array_key_exists('coordinates', $point['geometry'])) {
            throw new GeocodingFailureException('key error: coordinates');
        }

        // Caution: GeoJSON uses (lon, lat), but we process (lat, lon). (There's no standard on this.)
        $lonLat = $point['geometry']['coordinates'];

        // Phew. Let's do a final check on the coordinates.

        if (\count($lonLat) != 2) {
            $message = sprintf('expected 2 coordinates, got %d', \count($lonLat));
            throw new GeocodingFailureException($message);
        }

        $longitude = $lonLat[0];
        $latitude = $lonLat[1];

        return Coordinates::fromLatLon($latitude, $longitude);
    }
}
