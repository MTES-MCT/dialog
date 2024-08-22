<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Infrastructure\Litteralis\LitteralisClient;
use App\Infrastructure\Litteralis\LitteralisReporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LitteralisClientTest extends TestCase
{
    private MockHttpClient $httpClient;
    private $reporter;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient(baseUri: 'http://testserver');
        $this->reporter = $this->createMock(LitteralisReporter::class);
    }

    public function testNotConfigured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Credentials not set');

        $extractor = new LitteralisClient($this->httpClient);
        $extractor->count(null, $this->reporter);
    }

    public function testCredentialsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Credentials are empty');

        $extractor = new LitteralisClient($this->httpClient);
        $extractor->setCredentials('');
    }

    public function testFetchAllPaginated(): void
    {
        $features = [
            [
                'geometry' => [
                    'type' => 'Polygon',
                ],
                'properties' => [
                    'arretesrcid' => 'arrete1',
                ],
            ],
            [
                'geometry' => [
                    'type' => 'Polygon',
                ],
                'properties' => [
                    'arretesrcid' => 'arrete2',
                ],
            ],
            [
                'geometry' => [
                    'type' => 'Polygon',
                ],
                'properties' => [
                    'arretesrcid' => 'arrete2',
                ],
            ],
        ];

        $this->httpClient->setResponseFactory([
            function (string $method, string $url, array $options) use ($features) {
                $this->assertSame($method, 'GET');
                $this->assertSame($url, 'http://testserver/maplink/public/wfs?outputFormat=application/json&SERVICE=wfs&VERSION=2&REQUEST=GetFeature&TYPENAME=litteralis:litteralis&cql_filter=test&count=1000&startIndex=0');
                $this->assertContains('Authorization: Basic Y3JlZGVudGlhbHM=', $options['headers']);

                return new MockResponse(
                    json_encode([
                        'features' => $features,
                        'totalFeatures' => 4,
                    ]),
                    ['http_code' => 200],
                );
            },
        ]);

        $client = new LitteralisClient($this->httpClient);
        $client->setCredentials('credentials');

        $this->assertEquals($features, $client->fetchAllPaginated('test', $this->reporter));
    }
}
