<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\MapGeocoderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IgnMapGeocoder implements MapGeocoderInterface
{
    public function __construct(
        private HttpClientInterface $ignGeocoderClient,
    ) {
    }

    public function findPlaces(string $search): array
    {
        $response = $this->ignGeocoderClient->request(
            'GET',
            '/geocodage/completion',
            [
                'query' => [
                    'text' => $search,
                    'type' => 'StreetAddress, PositionOfInterest',
                    'poiType' => 'administratif',
                ],
            ],
        );

        $data = json_decode($response->getContent(), true);

        $places = [];

        foreach ($data['results'] as $result) {
            $places[] = [
                'label' => $result['fulltext'],
                'value' => [
                    'coordinates' => [$result['x'], $result['y']],
                    'kind' => $result['kind'],
                ],
            ];
        }

        return $places;
    }
}
