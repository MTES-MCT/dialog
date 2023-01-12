<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Infrastructure\Adapter\IGNGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IGNGeocoderTest extends TestCase
{
    private $address = '15 Route du Grand Brossais, 44260 Savenay';

    public function testComputeCoordinates(): void
    {
        $body = '{"features": [{"geometry": {"coordinates": [0.5, 44.3]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $geocoder = new IGNGeocoder($http);

        $coords = $geocoder->computeCoordinates($this->address);

        $this->assertSame(44.3, $coords->getLatitude());
        $this->assertSame(0.5, $coords->getLongitude());
    }

    private function provideStatusErrorData(): array {
        return [
            [503, '/server error: HTTP 503$/'],
            // These could happen if IGN's geocoding API parameters or authentication change.
            // In that case, we want the app to fail loudly.
            [400, '/client error: HTTP 400$/'],
            [401, '/client error: HTTP 401$/'],
            [403, '/client error: HTTP 403$/'],
            // This shouldn't happen unless IGN's geoservice gets messed up.
            // But let's handle and test this case too.
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

        $geocoder = new IGNGeocoder($http);
        $geocoder->computeCoordinates($this->address);
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

        $geocoder = new IGNGeocoder($http);
        $geocoder->computeCoordinates($this->address);
    }
}
