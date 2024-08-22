<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Infrastructure\Litteralis\LitteralisClient;
use App\Infrastructure\Litteralis\LitteralisExtractor;
use App\Infrastructure\Litteralis\LitteralisReporter;
use PHPUnit\Framework\TestCase;

final class LitteralisExtractorTest extends TestCase
{
    private $client;
    private $reporter;

    protected function setUp(): void
    {
        $this->client = $this->createMock(LitteralisClient::class);
        $this->reporter = $this->createMock(LitteralisReporter::class);
    }

    public function testExtractFeaturesByRegulation(): void
    {
        $laterThan = new \DateTimeImmutable('2024-08-01');

        $features = [
            [
                'geometry' => [
                    'type' => 'Polygon',
                ],
                'properties' => [
                    'arretesrcid' => 'arrete1',
                    'collectiviteagenceid' => 173214,
                ],
            ],
            [
                'geometry' => [
                    'type' => 'Polygon',
                ],
                'properties' => [
                    'arretesrcid' => 'arrete2',
                    'collectiviteagenceid' => 173214,
                ],
            ],
            [
                'geometry' => [
                    'type' => 'Polygon',
                ],
                'properties' => [
                    'arretesrcid' => 'arrete2',
                    'collectiviteagenceid' => 173214,
                ],
            ],
            [
                // 'geometry' missing
                'properties' => [
                    'idemprise' => 'emprise4',
                    'arretesrcid' => 'arrete3',
                    'shorturl' => 'https://dl.sogelink.fr/?n3omzTyS',
                    'collectiviteagenceid' => 173214,
                ],
            ],
        ];

        $cqlFilter = "(mesures ILIKE '%circulation interdite%' OR mesures ILIKE '%limitation de vitesse%') AND (arretefin IS NULL OR arretefin >= '2024-08-01T00:00:00+0000')";

        $this->client
            ->expects(self::exactly(2))
            ->method('count')
            ->withConsecutive(
                [null, $this->reporter],
                [$cqlFilter, $this->reporter],
            )
            ->willReturnOnConsecutiveCalls(10, 4);

        $this->client
            ->expects(self::once())
            ->method('fetchAllPaginated')
            ->with($cqlFilter, $this->reporter)
            ->willReturn($features);

        $this->reporter
            ->expects(self::exactly(3))
            ->method('setCount')
            ->withConsecutive(
                [$this->reporter::COUNT_TOTAL_FEATURES, 10],
                [$this->reporter::COUNT_MATCHING_FEATURES, 4],
                [$this->reporter::COUNT_EXTRACTED_FEATURES, 3],
            );

        $this->reporter
            ->expects(self::once())
            ->method('addWarning')
            ->with($this->reporter::WARNING_MISSING_GEOMETRY, [
                'idemprise' => 'emprise4',
                'arretesrcid' => 'arrete3',
                'shorturl' => 'https://dl.sogelink.fr/?n3omzTyS',
            ]);

        $extractor = new LitteralisExtractor($this->client);

        $crs = [
            'type' => 'name',
            'properties' => ['name' => 'EPSG:4326'],
        ];

        $expectedResult = [
            '173214#arrete1' => [
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'crs' => $crs,
                    ],
                    'properties' => [
                        'arretesrcid' => 'arrete1',
                        'collectiviteagenceid' => 173214,
                    ],
                ],
            ],
            '173214#arrete2' => [
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'crs' => $crs,
                    ],
                    'properties' => [
                        'arretesrcid' => 'arrete2',
                        'collectiviteagenceid' => 173214,
                    ],
                ],
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'crs' => $crs,
                    ],
                    'properties' => [
                        'arretesrcid' => 'arrete2',
                        'collectiviteagenceid' => 173214,
                    ],
                ],
            ],
        ];

        $this->reporter
            ->expects(self::once())
            ->method('onExtract')
            ->with(json_encode($expectedResult, JSON_UNESCAPED_UNICODE & JSON_UNESCAPED_SLASHES));

        $this->assertEquals($expectedResult, $extractor->extractFeaturesByRegulation($laterThan, $this->reporter));
    }
}
