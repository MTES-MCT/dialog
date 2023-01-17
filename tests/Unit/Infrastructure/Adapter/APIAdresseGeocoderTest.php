<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Infrastructure\Adapter\APIAdresseGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class APIAdresseGeocoderTest extends TestCase
{
    private $postalCode = '44260';
    private $city = 'Savenay';
    private $roadName = 'Route du Grand Brossais';
    private $houseNumber = '15';

    public function testComputeCoordinates(): void
    {
        $body = '{"features": [{"geometry": {"coordinates": [0.5, 44.3]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);

        $coords = $geocoder->computeCoordinates($this->postalCode, $this->city, $this->roadName, $this->houseNumber);

        $this->assertSame(44.3, $coords->getLatitude());
        $this->assertSame(0.5, $coords->getLongitude());
    }

    private function provideStatusErrorData(): array {
        return [
            [503, '/server error: HTTP 503$/'],
            // This could happen if API Adresse is updated or if we use wrong parameters.
            [400, '/client error: HTTP 400$/'],
            // These cases shouldn't happen because API Adresse does not require authentication
            // and shouldn't mess with redirects.
            // But let's handle and test them too.
            [401, '/client error: HTTP 401$/'],
            [403, '/client error: HTTP 403$/'],
            [303, '/too many redirects: HTTP 303$/'],
        ];
    }

    /** 
     * @dataProvider provideStatusErrorData
    */
    public function testComputeCoordinatesStatusError(int $statusCode, string $pattern): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectErrorMessageMatches($pattern);

        $response = new MockResponse('...', ['http_code' => $statusCode]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $geocoder->computeCoordinates($this->postalCode, $this->city, $this->roadName, $this->houseNumber);
    }

    private function provideDecodeErrorData(): array {
        return [
            ['{"features": }', '/^invalid json/'],
            ['{}', '/^key error: features/'],
            ['{"features": []}', '/expected 1 result, got 0$/'],
            ['{"features": [{}]}', '/^key error: geometry/'],
            ['{"features": [{"geometry": {}}]}', '/^key error: coordinates/'],
            ['{"features": [{"geometry": {"coordinates": [42]}}]}', '/^expected 2 coordinates, got 1/'],
        ];
    }

    /**
     * @dataProvider provideDecodeErrorData
     */
    public function testComputeCoordinatesDecodeError(string $body, string $pattern): void {
        $this->expectException(GeocodingFailureException::class);
        $this->expectErrorMessageMatches($pattern);

        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new APIAdresseGeocoder($http);
        $geocoder->computeCoordinates($this->postalCode, $this->city, $this->roadName, $this->houseNumber);
    }
}
