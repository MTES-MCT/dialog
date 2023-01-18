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
        private string $apiAdresseSearchUrl,
    ) {
    }

    public function computeCoordinates(string $address, ?string $postalCodeHint = null): Coordinates
    {
        // See: https://adresse.data.gouv.fr/api-doc/adresse

        $query = [
            'q' => $address,
            'limit' => 1,
            'type' => 'housenumber',
        ];

        if ($postalCodeHint) {
            $query['postcode'] = $postalCodeHint;
        }

        $response = $this->http->request('GET', $this->apiAdresseSearchUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);

        $requestUrl = $response->getInfo()['url'];
        $errorMsgPrefix = sprintf('requesting %s', $requestUrl);

        // Check the response status...

        if ($response->getStatusCode() >= 500) {
            $message = sprintf('%s: server error: HTTP %d', $errorMsgPrefix, $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        if ($response->getStatusCode() >= 400) {
            $message = sprintf('%s: client error: HTTP %d', $errorMsgPrefix, $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        if ($response->getStatusCode() >= 300) {
            $message = sprintf('%s: too many redirects: HTTP %d', $errorMsgPrefix, $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        // API Adresse returns a GeoJSON `FeatureCollection` object.
        // Decode it carefully according to the spec.
        // See: https://www.rfc-editor.org/rfc/rfc7946#section-3.3

        try {
            $data = $response->toArray(false);
        } catch (DecodingExceptionInterface $exc) {
            $message = sprintf('%s: invalid json: %s', $errorMsgPrefix, $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        // We want $data['features'][0]['geometry']['coordinates'].

        if (!\array_key_exists('features', $data)) {
            $message = sprintf('%s: key error: features', $errorMsgPrefix);
            throw new GeocodingFailureException($message);
        }

        if (\count($data['features']) === 0) {
            $message = sprintf('%s: error: expected 1 result, got 0', $errorMsgPrefix);
            throw new GeocodingFailureException($message);
        }

        $point = $data['features'][0];

        if (!\array_key_exists('geometry', $point)) {
            $message = sprintf('%s: key error: geometry', $errorMsgPrefix);
            throw new GeocodingFailureException($message);
        }

        if (!\array_key_exists('coordinates', $point['geometry'])) {
            $message = sprintf('%s: key error: coordinates', $errorMsgPrefix);
            throw new GeocodingFailureException($message);
        }

        // Caution: GeoJSON uses (longitude, latitude).
        // See: https://www.rfc-editor.org/rfc/rfc7946#section-3.1.1
        // But we process (latitude, longitude). (There's no standard on this.)
        $lonLat = $point['geometry']['coordinates'];

        // Phew. Let's do a final check on the coordinates.

        if (\count($lonLat) !== 2) {
            $message = sprintf('%s: expected 2 coordinates, got %d', $errorMsgPrefix, \count($lonLat));
            throw new GeocodingFailureException($message);
        }

        $longitude = $lonLat[0];
        $latitude = $lonLat[1];

        return Coordinates::fromLatLon($latitude, $longitude);
    }
}
