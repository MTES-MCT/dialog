<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Infrastructure\Adapter\IgnWfsRoadsNumbers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IgnWfsRoadsNumbersTest extends TestCase
{
    private string $departmentalRoadNumber = 'D32';
    private string $gestionnaire = 'Ardennes';
    private string $roadType = 'Départementale';
    private string $baseUrl = 'http://testserver';
    private string $ignWfsUrl = 'http://testserver/wfs/ows';

    public function testGetDepartmentalRoad(): void
    {
        $body = '{"type":"FeatureCollection","features":[{"type":"Feature","id":"route_numerotee_ou_nommee.48154","geometry":{"type":"MultiLineString","coordinates":[]},"geometry_name":"geometrie","properties":{"numero":"D32"},"bbox":[4.37161876,49.78157612,4.44694714,49.94114102]}],"totalFeatures":1,"numberMatched":1,"numberReturned":1,"timeStamp":"2024-03-05T10:05:03.145Z","crs":{"type":"name","properties":{"name":"urn:ogc:def:crs:EPSG::4326"}},"bbox":[4.37161876,49.56433173,4.89664925,49.94114102]}';

        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], $this->baseUrl);

        $IgnWfsRoadsNumbers = new IgnWfsRoadsNumbers($this->ignWfsUrl, $http);

        $geometry = $IgnWfsRoadsNumbers->getDepartmentalRoad($this->departmentalRoadNumber, $this->gestionnaire, $this->roadType);
        $this->assertSame('D32', $geometry[0]['numero']);
        $this->assertSame('{"type":"MultiLineString","coordinates":[]}', $geometry[0]['geometry']);
        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'http://testserver/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:route_numerotee_ou_nommee&cql_filter=strStartsWith(numero%2C%20%27D32%27)%3Dtrue%20AND%20gestionnaire%3D%27Ardennes%27%20AND%20type_de_route%3D%27D%C3%A9partementale%27&PropertyName=numero%2Cgeometrie',
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

        $IgnWfsRoadsNumbers = new IgnWfsRoadsNumbers($this->ignWfsUrl, $http);
        $IgnWfsRoadsNumbers->getDepartmentalRoad($this->departmentalRoadNumber, $this->gestionnaire, $this->roadType);
    }
}
