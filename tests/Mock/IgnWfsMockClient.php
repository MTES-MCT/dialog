<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IgnWfsMockClient extends MockHttpClient
{
    public function __construct()
    {
        $callback = fn ($method, $url, $options) => $this->handleRequests($method, $url, $options);
        parent::__construct($callback);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        if ($method === 'GET' && str_starts_with($url, 'https://data.geopf.fr/wfs/ows')) {
            return $this->getWfsMock($options);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getWfsMock($options): MockResponse
    {
        if (str_contains($options['query']['cql_filter'], "nom_minuscule='rue saint-victor'")) {
            $body = [
                'features' => [
                    [
                        'geometry' => [
                            'type' => 'MultiLineString',
                            'coordinates' => [
                                [
                                    [3.06773711, 50.65302845],
                                    [3.06770772, 50.65320974],
                                    [3.0676489, 50.65356155],
                                    [3.06761116, 50.65383438],
                                    [3.06756215, 50.65413324],
                                    [3.06756371, 50.65424093],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        } else {
            $body = [
                'features' => [],
            ];
        }

        return new MockResponse(
            json_encode($body),
            ['http_code' => 200],
        );
    }
}
