<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Infrastructure\Adapter\RoadGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RoadGeocoderTest extends TestCase
{
    private string $address = 'Rue Saint-Victor 59110 La Madeleine';
    private string $inseeCode = '59368';
    private string $departmentalRoadNumber = 'D32';
    private string $gestionnaire = 'Ardennes';
    private string $baseUrl = 'http://testserver';
    private string $ignWfsUrl = 'http://testserver/wfs/ows';

    public function testComputeRoadLine(): void
    {
        $body = '{"features": [{"geometry": {"type": "MultiLineString", "coordinates": ["..."]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], $this->baseUrl);

        $roadGeocoder = new RoadGeocoder($this->ignWfsUrl, $http);

        $geometry = $roadGeocoder->computeRoadLine($this->address, $this->inseeCode);

        $this->assertSame('{"type":"MultiLineString","coordinates":["..."]}', $geometry);

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'http://testserver/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=strStripAccents(strReplace(nom_minuscule%2C%20%27-%27%2C%20%27%20%27%2C%20true))%3DstrStripAccents(%27rue%20saint%20victor%2059110%20la%20madeleine%27)%20AND%20code_insee%3D%2759368%27&PropertyName=geometrie',
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

        $roadGeocoder = new RoadGeocoder($this->ignWfsUrl, $http);
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

        $RoadGeocoder = new RoadGeocoder($this->ignWfsUrl, $http);

        $RoadGeocoder->computeRoadLine($this->address, $this->inseeCode);
    }

    private function provideDecodeErrorDataForDepartmental(): array
    {
        return [
            ['{"features": }', '/invalid json: Syntax error/'],
        ];
    }

    /**
     * @dataProvider provideDecodeErrorDataForDepartmental
     */
    public function testGetDepartmentalRoadError(string $body, string $pattern): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches($pattern);

        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);

        $RoadGeocoder = new RoadGeocoder($this->ignWfsUrl, $http);

        $RoadGeocoder->findDepartmentalRoads($this->departmentalRoadNumber, $this->gestionnaire);
    }

    public function testGetDepartmentalRoad(): void
    {
        $body = '{"type":"FeatureCollection","features":[{"type":"Feature","id":"route_numerotee_ou_nommee.48154","geometry":{"type":"MultiLineString","coordinates":[]},"geometry_name":"geometrie","properties":{"numero":"D32"},"bbox":[4.37161876,49.78157612,4.44694714,49.94114102]}],"totalFeatures":1,"numberMatched":1,"numberReturned":1,"timeStamp":"2024-03-05T10:05:03.145Z","crs":{"type":"name","properties":{"name":"urn:ogc:def:crs:EPSG::4326"}},"bbox":[4.37161876,49.56433173,4.89664925,49.94114102]}';

        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], $this->baseUrl);

        $RoadGeocoder = new RoadGeocoder($this->ignWfsUrl, $http);

        $geometry = $RoadGeocoder->findDepartmentalRoads($this->departmentalRoadNumber, $this->gestionnaire);
        $this->assertSame('D32', $geometry[0]['roadNumber']);
        $this->assertSame('{"type":"MultiLineString","coordinates":[]}', $geometry[0]['geometry']);
        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'http://testserver/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:route_numerotee_ou_nommee&cql_filter=strStartsWith(numero%2C%20%27D32%27)%3Dtrue%20AND%20gestionnaire%3D%27Ardennes%27%20AND%20type_de_route%3D%27D%C3%A9partementale%27&PropertyName=numero%2Cgeometrie',
            $response->getRequestUrl(),
        );
    }
}
