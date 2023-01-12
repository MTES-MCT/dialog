<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Domain\Geography\Coordinates;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IGNGeocoder implements GeocoderInterface
{
    public function __construct(
        private HttpClientInterface $http,
    ) {
    }

    public function computeCoordinates(string $address): Coordinates
    {
        // See: https://geoservices.ign.fr/documentation/services/api-et-services-ogc/geocodage-20/doc-technique-api-geocodage

        $url = 'https://wxs.ign.fr/essentiels/geoportail/geocodage/rest/0.1/search';

        $response = $this->http->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'q' => $address,
                'type' => 'housenumber',
                'limit' => 1,
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

        // ... And be very defensive when decoding the response data.

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

        $coords = $point['geometry']['coordinates'];

        // Phew. Let's do a final check on the coordinates.

        if (\count($coords) != 2) {
            $message = sprintf('expected 2 coordinates, got %d', \count($coords));
            throw new GeocodingFailureException($message);
        }

        // Caution: IGN's geocoding API returns (lon, lat), but we process (lat, lon). (There's no standard on this.)
        $longitude = $coords[0];
        $latitude = $coords[1];

        return Coordinates::fromLatLon($latitude, $longitude);
    }
}
