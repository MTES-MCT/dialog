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
        $query = $options['query']['q'];

        if (str_contains($options['query']['q'], 'GEOCODING_FAILURE')) {
            throw new GeocodingFailureException();
        }

        if ($query === 'Rue Eugène Berthoud') {
            $body = [
                'features' => [
                    [
                        'properties' => [
                            'type' => 'street',
                            'name' => 'Rue Eugène Berthoud',
                            'postcode' => '93400',
                            'city' => 'Saint-Ouen-sur-Seine',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'housenumber',
                            'label' => '10 Rue Eugène Berthoud, 93400 Saint-Ouen-sur-Seine',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'street',
                            'name' => 'Impasse Eugène Berthou',
                            'city' => 'Le Relecq-Kerhuon',
                            'postcode' => '29480',
                        ],
                    ],
                ],
            ];
        } elseif ($query === 'Le Mesnil') {
            $body = [
                'features' => [
                    [
                        'properties' => [
                            'type' => 'municipality',
                            'city' => 'Le Mesnil',
                            'postcode' => '50580',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'street',
                            'name' => 'Rue Le Mesnil',
                            'city' => 'Saon',
                            'postcode' => '14330',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'municipality',
                            'city' => 'Le Mesnil-Esnard',
                            'postcode' => '76240',
                        ],
                    ],
                    [
                        'properties' => [
                            'type' => 'municipality',
                            'city' => 'Le Mesnil-le-Roi',
                            'postcode' => '78600',
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
