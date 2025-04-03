<?php

declare(strict_types=1);

namespace App\Tests\Mock;

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

        $features = match ([$type, $query]) {
            ['municipality', 'Dijon'] => [
                [
                    'properties' => [
                        'city' => 'Dijon',
                        'postcode' => '21000',
                        'citycode' => '21231',
                    ],
                ],
            ],
            ['municipality', 'Mesnil'] => [
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
            ['street', 'Rue Eugène Berthoud'] => [
                [
                    'properties' => [
                        'name' => 'Rue Eugène Berthoud',
                        'label' => 'Rue Eugène Berthoud, 93400 Saint-Ouen-sur-Seine',
                    ],
                ],
            ],
            ['housenumber', '55 rue Eugène Berthoud'] => [
                [
                    'geometry' => json_decode('{"type":"Point","coordinates":[-1.935828977,47.34702398]}', true),
                ],
            ],
            ['housenumber', '37bis Route du Grand Brossais'] => [
                [
                    'geometry' => json_decode('{"type":"Point","coordinates":[-1.930970945,47.347922986]}', true),
                ],
            ],
            ['housenumber', '999 Route du Grand Brossais'] => [], // Simulate not found by API Adresse
            ['housenumber', '80 Rue du Faubourg de Paris'] => [
                [
                    'geometry' => json_decode('{"type":"Point","coordinates":[3.512442952,50.353245966]}', true),
                ],
            ],
            ['housenumber', '44 Rue du Faubourg de Paris'] => [
                [
                    'geometry' => json_decode('{"type":"Point","coordinates":[3.514232943,50.353508042]}', true),
                ],
            ],
            default => [
                [
                    'geometry' => ['coordinates' => [0.4, 44.5]],
                ],
            ]
        };

        return new MockResponse(
            json_encode(['features' => $features], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        );
    }
}
