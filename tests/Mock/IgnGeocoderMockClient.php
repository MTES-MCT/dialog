<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IgnGeocoderMockClient extends MockHttpClient
{
    // Contours de collectivités servis par le WFS (ADMIN EXPRESS COG), réduits à des rectangles
    // approximatifs [minLon, minLat, maxLon, maxLat] englobant les emprises des fixtures.
    // Clé : couche WFS + filtre CQL.
    private const ADMINISTRATIVE_BOUNDARIES = [
        "commune|code_insee='93070'" => ['Saint-Ouen-sur-Seine', [2.31, 48.89, 2.36, 48.92]],
        "epci|code_siren='200054781'" => ['Métropole du Grand Paris', [2.14, 48.68, 2.62, 49.02]],
        "departement|code_insee='93'" => ['Seine-Saint-Denis', [2.28, 48.80, 2.60, 49.01]],
        "departement|code_insee='08'" => ['Ardennes', [4.00, 49.20, 5.40, 50.20]],
        "departement|code_insee='59'" => ['Nord', [2.90, 50.40, 3.20, 50.70]],
        "region|code_insee='11'" => ['Île-de-France', [1.44, 48.12, 3.56, 49.24]],
    ];

    // Code de département pour lequel le WFS simule une panne (erreur HTTP 500).
    public const UNAVAILABLE_DEPARTMENT_CODE = '500';

    private string $baseUri = 'https://testserver';
    private array $requests;

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $this->requests[] = ['url' => $url, 'options' => $options];

        if (preg_match('/\/geocodage\/completion/', $url)) {
            return new MockResponse($this->getGeocodageCompletionJSON($options['query']['text']), ['http_code' => 200]);
        }

        if (preg_match('/\/wfs\/ows/', $url)) {
            return $this->getAdministrativeBoundaryResponse($options['query']['TYPENAMES'], $options['query']['CQL_FILTER']);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getAdministrativeBoundaryResponse(string $typeName, string $cqlFilter): MockResponse
    {
        if ($cqlFilter === \sprintf("code_insee='%s'", self::UNAVAILABLE_DEPARTMENT_CODE)) {
            return new MockResponse('Internal Server Error', ['http_code' => 500]);
        }

        // 'ADMINEXPRESS-COG-CARTO.LATEST:departement' => 'departement'
        $layer = substr($typeName, strrpos($typeName, ':') + 1);
        $boundary = self::ADMINISTRATIVE_BOUNDARIES[\sprintf('%s|%s', $layer, $cqlFilter)] ?? null;

        if ($boundary === null) {
            // Code inconnu du COG : le WFS renvoie une collection vide.
            return new MockResponse(json_encode(['type' => 'FeatureCollection', 'features' => []]), ['http_code' => 200]);
        }

        [$name, [$minLon, $minLat, $maxLon, $maxLat]] = $boundary;

        return new MockResponse(
            json_encode([
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'MultiPolygon',
                            'coordinates' => [[[
                                [$minLon, $minLat],
                                [$maxLon, $minLat],
                                [$maxLon, $maxLat],
                                [$minLon, $maxLat],
                                [$minLon, $minLat],
                            ]]],
                        ],
                        'properties' => ['nom_officiel' => $name],
                    ],
                ],
            ]),
            ['http_code' => 200],
        );
    }

    private function getGeocodageCompletionJSON(string $text): string
    {
        if ($text === 'Par') {
            return json_encode([
                'results' => [
                    [
                        'fulltext' => 'Rue du Parc',
                        'x' => 'x1',
                        'y' => 'y1',
                        'kind' => 'street',
                    ],
                    [
                        'fulltext' => 'Paris',
                        'x' => 'x2',
                        'y' => 'y2',
                        'kind' => 'administratif',
                    ],
                ],
            ]);
        }

        return json_encode([
            'results' => [],
        ]);
    }
}
