<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Infrastructure\Litteralis\LitteralisExtractor;
use App\Infrastructure\Litteralis\LitteralisReporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LitteralisExtractorTest extends TestCase
{
    private MockHttpClient $httpClient;
    private $reporter;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient(baseUri: 'http://testserver');
        $this->reporter = $this->createMock(LitteralisReporter::class);
    }

    public function testExtractFeaturesByRegulation(): void
    {
        $this->httpClient->setResponseFactory([
            // Request for total feature count
            function (string $method, string $url, array $options) {
                $this->assertSame($method, 'GET');
                $this->assertSame($url, 'http://testserver/maplink/public/wfs?outputFormat=application/json&SERVICE=wfs&VERSION=2&REQUEST=GetFeature&TYPENAME=litteralis:litteralis&count=1&startIndex=0');

                return new MockResponse('{"numberMatched": 10}', ['http_code' => 200]);
            },

            // Request for number of features matching filter
            function (string $method, string $url, array $options) {
                $this->assertSame($method, 'GET');
                $this->assertSame($url, 'http://testserver/maplink/public/wfs?outputFormat=application/json&SERVICE=wfs&VERSION=2&REQUEST=GetFeature&TYPENAME=litteralis:litteralis&count=1&startIndex=0&cql_filter=mesures%20ILIKE%20%27%25circulation%20interdite%25%27%20OR%20mesures%20ILIKE%20%27%25limitation%20de%20vitesse%25%27');

                return new MockResponse('{"numberMatched": 4}', ['http_code' => 200]);
            },

            // Request for features
            function (string $method, string $url, array $options) {
                $this->assertSame($method, 'GET');
                $this->assertSame($url, 'http://testserver/maplink/public/wfs?outputFormat=application/json&SERVICE=wfs&VERSION=2&REQUEST=GetFeature&TYPENAME=litteralis:litteralis&cql_filter=mesures%20ILIKE%20%27%25circulation%20interdite%25%27%20OR%20mesures%20ILIKE%20%27%25limitation%20de%20vitesse%25%27&count=1000&startIndex=0');

                return new MockResponse(
                    json_encode([
                        'features' => [
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
                            [
                                // 'geometry' missing
                                'properties' => [
                                    'idemprise' => 'emprise4',
                                    'arretesrcid' => 'arrete3',
                                ],
                            ],
                        ],
                        'totalFeatures' => 4,
                    ]),
                    ['http_code' => 200],
                );
            },
        ]);

        $this->reporter
            ->expects(self::exactly(3))
            ->method('setCount')
            ->withConsecutive(
                [$this->reporter::COUNT_TOTAL_FEATURES, 10],
                [$this->reporter::COUNT_MATCHING_FEATURES, 4, ['%cqlFilter%' => "mesures ILIKE '%circulation interdite%' OR mesures ILIKE '%limitation de vitesse%'"]],
                [$this->reporter::COUNT_EXTRACTED_FEATURES, 3],
            );

        $this->reporter
            ->expects(self::once())
            ->method('addWarning')
            ->with($this->reporter::WARNING_MISSING_GEOMETRY, [
                'idemprise' => 'emprise4',
                'arretesrcid' => 'arrete3',
            ]);

        $extractor = new LitteralisExtractor($this->httpClient);

        $crs = [
            'type' => 'name',
            'properties' => ['name' => 'EPSG:4326'],
        ];

        $expectedResult = [
            'arrete1' => [
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'crs' => $crs,
                    ],
                    'properties' => [
                        'arretesrcid' => 'arrete1',
                    ],
                ],
            ],
            'arrete2' => [
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'crs' => $crs,
                    ],
                    'properties' => [
                        'arretesrcid' => 'arrete2',
                    ],
                ],
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'crs' => $crs,
                    ],
                    'properties' => [
                        'arretesrcid' => 'arrete2',
                    ],
                ],
            ],
        ];

        $this->reporter
            ->expects(self::once())
            ->method('onExtract')
            ->with(json_encode($expectedResult, JSON_UNESCAPED_UNICODE & JSON_UNESCAPED_SLASHES));

        $this->assertEquals($expectedResult, $extractor->extractFeaturesByRegulation($this->reporter));
    }
}
