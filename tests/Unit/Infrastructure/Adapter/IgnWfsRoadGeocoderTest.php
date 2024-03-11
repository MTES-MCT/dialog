<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Infrastructure\Adapter\IgnWfsRoadGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IgnWfsRoadGeocoderTest extends TestCase
{
    private string $address = 'Rue de l’Eglise Saint-Victor 59110 La Madeleine';
    private string $inseeCode = '59368';
    private string $baseUrl = 'http://testserver';
    private string $ignWfsUrl = 'http://testserver/wfs/ows';

    public function testComputeRoadLine(): void
    {
        $body = '{"features": [{"geometry": {"type": "MultiLineString", "coordinates": ["..."]}, "properties": {"id_pseudo_fpb": "id"}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], $this->baseUrl);

        $roadGeocoder = new IgnWfsRoadGeocoder($this->ignWfsUrl, $http);

        $roadLine = $roadGeocoder->computeRoadLine($this->address, $this->inseeCode);

        $this->assertSame('{"type":"MultiLineString","coordinates":["..."],"crs":{"type":"name","properties":{"name":"EPSG:2154"}}}', $roadLine->geometry);
        $this->assertSame('id', $roadLine->id);
        $this->assertSame($this->address, $roadLine->roadName);
        $this->assertSame($this->inseeCode, $roadLine->cityCode);

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'http://testserver/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=strStripAccents(strReplace(nom_minuscule%2C%20%27-%27%2C%20%27%20%27%2C%20true))%3DstrStripAccents(strReplace(%27rue%20de%20l%27%27eglise%20saint%20victor%2059110%20la%20madeleine%27%2C%20%27-%27%2C%20%27%20%27%2C%20true))%20AND%20code_insee%3D%2759368%27&PropertyName=geometrie%2Cid_pseudo_fpb',
            $response->getRequestUrl(),
        );
    }

    private function provideStatusErrorData(): array
    {
        return [
            [503, '/HTTP 503 returned/'],
            [400, '/HTTP 400 returned/'],
            [303, '/HTTP 303 returned/'],
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

        $roadGeocoder = new IgnWfsRoadGeocoder($this->ignWfsUrl, $http);
        $roadGeocoder->computeRoadLine($this->address, $this->inseeCode);
    }

    private function provideDecodeErrorData(): array
    {
        return [
            ['{"features": }', '/invalid json: Syntax error/'],
            ['{}', '/could not retrieve geometry/'],
            ['{"features": []}', '/could not retrieve geometry /'],
            ['{"features": [{}]}', '/could not retrieve geometry/'],
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

        $roadGeocoder = new IgnWfsRoadGeocoder($this->ignWfsUrl, $http);
        $roadGeocoder->computeRoadLine($this->address, $this->inseeCode);
    }

    public function testNetworkError(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/Idle timeout reached/i');

        $response = new MockResponse([new TransportException('Idle timeout reached')]);
        $http = new MockHttpClient([$response]);

        $roadGeocoder = new IgnWfsRoadGeocoder($this->ignWfsUrl, $http);
        $roadGeocoder->computeRoadLine($this->address, $this->inseeCode);
    }
}
