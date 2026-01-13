<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Infrastructure\Adapter\APIAdresseGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class APIAdresseGeocoderTest extends TestCase
{
    private string $address = '15 Route du Grand Brossais';
    private string $cityCode = '44195';

    public function testComputeCoordinates(): void
    {
        $body = '{"features": [{"geometry": {"coordinates": [0.5, 44.3]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], 'https://testserver');

        $geocoder = new APIAdresseGeocoder($http);

        $coords = $geocoder->computeCoordinates($this->address, $this->cityCode);

        $this->assertSame(44.3, $coords->latitude);
        $this->assertSame(0.5, $coords->longitude);

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'https://testserver/geocodage/search?q=15%20Route%20du%20Grand%20Brossais&limit=1&type=housenumber&citycode=44195',
            $response->getRequestUrl(),
        );
    }

    private function provideStatusErrorData(): array
    {
        return [
            [503, '/requesting https:\/\/.*: server error: HTTP 503$/'],
            // This could happen if API Adresse is updated or if we use wrong parameters.
            [400, '/requesting https:\/\/.*: client error: HTTP 400$/'],
            // These cases shouldn't happen because API Adresse does not require authentication
            // and shouldn't mess with redirects.
            // But let's handle and test them too.
            [401, '/requesting https:\/\/.*: client error: HTTP 401$/'],
            [403, '/requesting https:\/\/.*: client error: HTTP 403$/'],
            [303, '/requesting https:\/\/.*: too many redirects: HTTP 303$/'],
        ];
    }

    /**
     * @dataProvider provideStatusErrorData
     */
    public function testComputeCoordinatesStatusError(int $statusCode, string $pattern): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches($pattern);

        $response = new MockResponse('...', ['http_code' => $statusCode]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $geocoder->computeCoordinates($this->address, $this->cityCode);
    }

    private function provideDecodeErrorData(): array
    {
        return [
            ['{"features": }', '/^requesting https:\/\/.*: invalid json: .*$/'],
            ['{}', '/^requesting https:\/\/.*: key error: features$/'],
            ['{"features": []}', '/requesting https:\/\/.*: expected 1 result, got 0$/'],
            ['{"features": [{}]}', '/^requesting https:\/\/.*: key error: geometry$/'],
            ['{"features": [{"geometry": {}}]}', '/^requesting https:\/\/.*: key error: coordinates$/'],
            ['{"features": [{"geometry": {"coordinates": [42]}}]}', '/^requesting https:\/\/.*: expected 2 coordinates, got 1$/'],
        ];
    }

    /**
     * @dataProvider provideDecodeErrorData
     */
    public function testComputeCoordinatesDecodeError(string $body, string $pattern): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches($pattern);

        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $geocoder->computeCoordinates($this->address, $this->cityCode);
    }

    public function testFindCities(): void
    {
        $expectedRequests = [
            function ($method, $url, $options) {
                $this->assertSame('GET', $method);
                $this->assertEmpty(
                    array_diff(
                        ['scheme' => 'http', 'host' => 'testserver', 'path' => '/geocodage/search'],
                        parse_url($url),
                    ),
                );
                $this->assertContains('Accept: application/json', $options['headers']);
                $this->assertSame('Mesnil', $options['query']['q']);
                $this->assertSame('1', $options['query']['autocomplete']);
                $this->assertSame(7, $options['query']['limit']);
                $this->assertSame('municipality', $options['query']['type']);

                return new MockResponse(
                    json_encode([
                        'features' => [
                            [
                                'properties' => [
                                    'city' => 'Blanc Mesnil',
                                    'postcode' => '93150',
                                    'citycode' => '93007',
                                ],
                            ],
                        ],
                    ]),
                    ['http_code' => 200],
                );
            },
        ];

        $http = new MockHttpClient($expectedRequests, 'http://testserver');

        $geocoder = new APIAdresseGeocoder($http);
        $cities = $geocoder->findCities('Mesnil');
        $this->assertEquals([['code' => '93007', 'label' => 'Blanc Mesnil (93150)']], $cities);
    }

    public function testFindCitiesIgnoreCityWithArrondissements(): void
    {
        $expectedRequests = [
            function ($method, $url, $options) {
                return new MockResponse(
                    json_encode([
                        'features' => [
                            [
                                'properties' => [
                                    'city' => 'Paris (75001)',
                                    'postcode' => '75001',
                                    'citycode' => '75056',
                                ],
                            ],
                            [
                                'properties' => [
                                    'city' => 'Marseille (13001)',
                                    'postcode' => '13001',
                                    'citycode' => '13055',
                                ],
                            ],
                            [
                                'properties' => [
                                    'city' => 'Lyon (69001)',
                                    'postcode' => '69001',
                                    'citycode' => '69123',
                                ],
                            ],
                        ],
                    ]),
                    ['http_code' => 200],
                );
            },
        ];

        $http = new MockHttpClient($expectedRequests, 'http://testserver');

        $geocoder = new APIAdresseGeocoder($http);
        $cities = $geocoder->findCities('test');
        $this->assertEquals([], $cities);
    }

    public function testfindCitiesIncompleteFeature(): void
    {
        $body = json_encode(['features' => [[]]]);
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $addresses = $geocoder->findCities('Test');
        $this->assertEquals([], $addresses);
    }

    public function testfindCitiesAPIFailure(): void
    {
        $response = new MockResponse('...', ['http_code' => 500]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $cities = $geocoder->findCities('Test');
        $this->assertEquals([], $cities);
    }

    public function testfindCitiesInvalidJSON(): void
    {
        $response = new MockResponse('{"blah', ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $cities = $geocoder->findCities('Test');
        $this->assertEquals([], $cities);
    }

    public function testfindCitiesSearchTooShort(): void
    {
        $response = new MockResponse(
            json_encode([
                'features' => [
                    [
                        'properties' => [
                            'city' => 'Blanc Mesnil',
                            'postcode' => '93150',
                            'citycode' => '93007',
                        ],
                    ],
                ],
            ]),
            ['http_code' => 200],
        );
        $http = new MockHttpClient([$response]);
        $geocoder = new APIAdresseGeocoder($http);

        $cities = $geocoder->findCities('aa');
        $this->assertEquals([], $cities);
        $this->assertEquals(0, $http->getRequestsCount());

        $cities = $geocoder->findCities('aaa');
        $this->assertEquals([['code' => '93007', 'label' => 'Blanc Mesnil (93150)']], $cities);
        $this->assertEquals(1, $http->getRequestsCount());
    }

    public function testFindNamedStreets(): void
    {
        $features = [
            [
                'properties' => [
                    'citycode' => '59606',
                    'id' => '59606_1480',
                    'street' => 'Rue de Famars',
                ],
            ],
            [
                'properties' => [
                    'citycode' => '59606',
                    'id' => '59606_3190',
                    'street' => 'Rue du Quesnoy',
                ],
            ],
        ];

        $response = new MockResponse(json_encode(['features' => $features]), ['http_code' => 200]);
        $http = new MockHttpClient([$response]);
        $geocoder = new APIAdresseGeocoder($http);

        $this->assertEquals([
            ['roadBanId' => '59606_1480', 'roadName' => 'Rue de Famars'],
            ['roadBanId' => '59606_3190', 'roadName' => 'Rue du Quesnoy'],
        ], $geocoder->findNamedStreets('Rue', '59606'));

        $this->assertEquals([], $geocoder->findNamedStreets('aa', '59606'));
    }

    public function testFindNamedStreetsError(): void
    {
        $response = new MockResponse('...', ['http_code' => 500]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $this->assertEquals([], $geocoder->findNamedStreets('Rue Test', '59606'));
    }

    public function testGetRoadBanId(): void
    {
        $features = [
            [
                'properties' => [
                    'citycode' => '59606',
                    'id' => '59606_3210',
                    'street' => 'Rue des Récollets',
                ],
            ],
            [
                'properties' => [
                    'citycode' => '59606',
                    'id' => '59606_12345',
                    'street' => 'Rue des Ricola',
                ],
            ],
        ];
        $response = new MockResponse(json_encode(['features' => $features]), ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $this->assertSame('59606_3210', $geocoder->getRoadBanId('Recolet', '59606'));
    }

    public function testGetRoadBanIdNoResults(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/^no named street found/');

        $features = [];
        $response = new MockResponse(json_encode(['features' => $features]), ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $geocoder->getRoadBanId('Récollets', '59606');
    }
}
