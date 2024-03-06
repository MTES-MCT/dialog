<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\IgnWfsRoadGeocoder;
use PHPUnit\Framework\TestCase;
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
        $body = '{"features": [{"geometry": {"type": "MultiLineString", "coordinates": ["..."]}}]}';
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], $this->baseUrl);

        $roadGeocoder = new IgnWfsRoadGeocoder($this->ignWfsUrl, $http);

        $geometry = $roadGeocoder->computeRoadLine($this->address, $this->inseeCode);

        $this->assertSame('{"type":"MultiLineString","coordinates":["..."]}', $geometry);
        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame(
            'http://testserver/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=strStripAccents(strReplace(nom_minuscule%2C%20%27-%27%2C%20%27%20%27%2C%20true))%3DstrStripAccents(strReplace(%27rue%20de%20l%27%27eglise%20saint%20victor%2059110%20la%20madeleine%27%2C%20%27-%27%2C%20%27%20%27%2C%20true))%20AND%20code_insee%3D%2759368%27&PropertyName=geometrie',
            $response->getRequestUrl(),
        );
    }
}
