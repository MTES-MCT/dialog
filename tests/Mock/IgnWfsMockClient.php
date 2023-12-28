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
        } elseif (str_contains($options['query']['cql_filter'], "nom_minuscule='rue monge'")) {
            // E2E tests
            $body = [
                'features' => [
                    [
                        'geometry' => [
                            'type' => 'MultiLineString',
                            'coordinates' => [[[5.03168932, 47.31771662], [5.03177572, 47.31772501], [5.03185628, 47.31775422], [5.03193864, 47.317796], [5.03200397, 47.31784259]], [[5.03200397, 47.31784259], [5.03190919, 47.31782264], [5.03181621, 47.31781526], [5.03173705, 47.31782295], [5.03169758, 47.31782994], [5.03165551, 47.31783788]], [[5.03213156, 47.31788989], [5.03200397, 47.31784259]], [[5.03256244, 47.31811288], [5.03232346, 47.31799189], [5.03213156, 47.31788989]], [[5.03256244, 47.31811288], [5.03277057, 47.3182236]], [[5.03413544, 47.31891111], [5.03421187, 47.31893589], [5.03454423, 47.31903993], [5.0346421, 47.31907154], [5.03473071, 47.3191033], [5.03485994, 47.31915867]], [[5.03485994, 47.31915867], [5.03496222, 47.31920191], [5.03510435, 47.31928316]], [[5.03510435, 47.31928316], [5.03521143, 47.31934793], [5.03530781, 47.31941017]], [[5.03530781, 47.31941017], [5.03581835, 47.31974252], [5.03588215, 47.31978373], [5.03622198, 47.31997949]], [[5.03277057, 47.3182236], [5.03291609, 47.31832461], [5.03328019, 47.31853256], [5.03346836, 47.31860581], [5.03364517, 47.31869367]], [[5.03364517, 47.31869367], [5.03376968, 47.31876443], [5.03385727, 47.31880432], [5.03393536, 47.31883807], [5.03402271, 47.31887167], [5.03413544, 47.31891111]]],
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