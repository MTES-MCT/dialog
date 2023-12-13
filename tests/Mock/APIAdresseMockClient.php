<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Application\Exception\GeocodingFailureException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class APIAdresseMockClient extends MockHttpClient
{
    private string $baseUri = 'https://testserver';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        if ($method === 'GET' && str_starts_with($url, $this->baseUri . '/search/')) {
            return $this->getSearchMock($options);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getSearchMock(array $options): MockResponse
    {
        $type = $options['query']['type'];
        $query = $options['query']['q'];

        if ($type === 'housenumber' && str_contains($query, 'GEOCODING_FAILURE')) {
            throw new GeocodingFailureException();
        }

        if ($type === 'street' && $query === 'Rue Eugène Berthoud') {
            $body = [
                'features' => [
                    [
                        'properties' => [
                            'name' => 'Rue Eugène Berthoud',
                            'label' => 'Rue Eugène Berthoud, 93400 Saint-Ouen-sur-Seine',
                        ],
                    ],
                ],
            ];
        } elseif ($type === 'municipality' && $query === 'Mesnil') {
            $body = [
                'features' => [
                    [
                        'properties' => [
                            'type' => 'municipality',
                            'city' => 'Blanc Mesnil',
                            'postcode' => '93150',
                            'citycode' => '93007',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'municipality',
                            'city' => 'Le Mesnil-Esnard',
                            'postcode' => '76240',
                            'citycode' => '76429',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'municipality',
                            'city' => 'Le Mesnil-le-Roi',
                            'postcode' => '78600',
                            'citycode' => '78396',
                        ],
                    ],
                ],
            ];
        } else {
            $body = ['features' => [['geometry' => ['coordinates' => [0.4, 44.5]]]]];
        }

        return new MockResponse(
            json_encode($body, JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        );
    }
}
