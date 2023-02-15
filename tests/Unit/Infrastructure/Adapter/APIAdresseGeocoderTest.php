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
    private string $address = '15 Route du Grand Brossais 44260 Savenay';
    private string $postalCode = '44260';

    public function testComputeCoordinates(): void
    {
        $body = '{"features": [{"geometry": {"coordinates": [0.5, 44.3]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], 'https://testserver');

        $geocoder = new APIAdresseGeocoder($http);

        $coords = $geocoder->computeCoordinates($this->address, postalCodeHint: $this->postalCode);

        $this->assertSame(44.3, $coords->latitude);
        $this->assertSame(0.5, $coords->longitude);

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'https://testserver/search/?q=15%20Route%20du%20Grand%20Brossais%2044260%20Savenay&limit=1&type=housenumber&postcode=44260',
            $response->getRequestUrl()
        );
    }

    public function testComputeCoordinatesPostCodeHintOmitted(): void
    {
        $body = '{"features": [{"geometry": {"coordinates": [0.5, 44.3]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], 'https://testserver');

        $geocoder = new APIAdresseGeocoder($http);

        $coords = $geocoder->computeCoordinates($this->address);

        $this->assertSame(44.3, $coords->latitude);
        $this->assertSame(0.5, $coords->longitude);

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'https://testserver/search/?q=15%20Route%20du%20Grand%20Brossais%2044260%20Savenay&limit=1&type=housenumber',
            $response->getRequestUrl()
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
        $geocoder->computeCoordinates($this->address);
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
        $geocoder->computeCoordinates($this->address);
    }
}
