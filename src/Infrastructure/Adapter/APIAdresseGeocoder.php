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
        private HttpClientInterface $apiAdresseClient,
    ) {
    }

    public function computeCoordinates(string $address, string $cityCode, string $type = 'housenumber'): Coordinates
    {
        // See: https://adresse.data.gouv.fr/api-doc/adresse

        $query = [
            'q' => $address,
            'limit' => 1,
            'type' => $type,
            'citycode' => $cityCode,
        ];

        $response = $this->apiAdresseClient->request('GET', '/search/', [
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

        // GeoJSON uses (longitude, latitude).
        // See: https://www.rfc-editor.org/rfc/rfc7946#section-3.1.1
        $lonLat = $point['geometry']['coordinates'];

        if (\count($lonLat) !== 2) {
            $message = sprintf('%s: expected 2 coordinates, got %d', $errorMsgPrefix, \count($lonLat));
            throw new GeocodingFailureException($message);
        }

        return Coordinates::fromLonLat($lonLat[0], $lonLat[1]);
    }

    public function computeJunctionCoordinates(string $address, string $roadName, string $cityCode): Coordinates
    {
        return $this->computeCoordinates($roadName . ' / ' . $address, $cityCode, type: 'poi');
    }

    public function findRoadNames(string $search, string $cityCode): array
    {
        if (\strlen($search) < 3) {
            // APIAdresse returns error if search string has length strictly less than 3.
            return [];
        }

        $response = $this->apiAdresseClient->request('GET', '/search/', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'q' => $search,
                'autocomplete' => '1',
                'limit' => 7,
                'type' => 'street',
                'citycode' => $cityCode,
            ],
        ]);

        try {
            $data = $response->toArray(throw: true);
            $roadNames = [];

            foreach ($data['features'] as $feature) {
                $roadNames[] = [
                    'value' => $feature['properties']['name'],
                    'label' => $feature['properties']['label'],
                ];
            }

            return $roadNames;
        } catch (\Exception $exc) {
            \Sentry\captureException($exc);

            return [];
        }
    }

    public function findCities(string $search): array
    {
        if (\strlen($search) < 3) {
            // APIAdresse returns error if search string has length strictly less than 3.
            return [];
        }

        $response = $this->apiAdresseClient->request('GET', '/search/', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'q' => $search,
                'autocomplete' => '1',
                'limit' => 7,
                'type' => 'municipality',
            ],
        ]);

        try {
            $data = $response->toArray(throw: true);
            $cities = [];

            foreach ($data['features'] as $feature) {
                $label = sprintf('%s (%s)', $feature['properties']['city'], $feature['properties']['postcode']);

                $cities[] = [
                    'label' => $label,
                    'code' => $feature['properties']['citycode'],
                ];
            }

            return $cities;
        } catch (\Exception $exc) {
            \Sentry\captureException($exc);

            return [];
        }
    }
}
