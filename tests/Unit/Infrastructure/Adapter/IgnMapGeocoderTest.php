<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\IgnMapGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class IgnMapGeocoderTest extends TestCase
{
    public function testFindPlacesEmpty(): void
    {
        $client = new MockHttpClient([new JsonMockResponse(['results' => []])]);
        $geocoder = new IgnMapGeocoder($client);
        $this->assertEquals([], $geocoder->findPlaces('Par'));
    }

    public function testFindPlaces(): void
    {
        $client = new MockHttpClient(
            [
                function ($method, $path): ResponseInterface {
                    $this->assertSame('GET', $method);
                    $this->assertSame(\sprintf(
                        'http://testserver/geocodage/completion?text=Par&type=%s&poiType=administratif',
                        rawurlencode('StreetAddress, PositionOfInterest'),
                    ), $path);

                    return new JsonMockResponse([
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
                },
            ],
            'http://testserver',
        );

        $geocoder = new IgnMapGeocoder($client);

        $results = $geocoder->findPlaces('Par');

        $this->assertEquals([
            ['label' => 'Rue du Parc', 'value' => ['coordinates' => ['x1', 'y1'], 'kind' => 'street']],
            ['label' => 'Paris', 'value' => ['coordinates' => ['x2', 'y2'], 'kind' => 'administratif']],
        ], $results);
    }
}
