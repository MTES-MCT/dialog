<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisClient;
use App\Infrastructure\Litteralis\LitteralisClientFactory;
use App\Infrastructure\Litteralis\LitteralisExtractor;
use App\Infrastructure\Litteralis\LitteralisRecordEnum;
use PHPUnit\Framework\TestCase;

final class LitteralisExtractorTest extends TestCase
{
    private $enabledOrgs;
    private $credentials;
    private $clientFactory;
    private $client;
    private $reporter;

    protected function setUp(): void
    {
        $this->enabledOrgs = ['test'];
        $this->credentials = (new LitteralisCredentials())
            ->add('test', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'testpassword');

        $this->clientFactory = $this->createMock(LitteralisClientFactory::class);
        $this->client = $this->createMock(LitteralisClient::class);
        $this->reporter = $this->createMock(Reporter::class);
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

        $this->clientFactory
            ->expects(self::once())
            ->method('create')
            ->with('testpassword')
            ->willReturn($this->client);

        $cqlFilter = "(mesures ILIKE '%circulation interdite%' OR mesures ILIKE '%limitation de vitesse%' OR mesures ILIKE '%interruption de circulation%') AND (arretefin IS NULL OR arretefin >= '2024-08-01T00:00:00+0000')";

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
            ->method('addCount')
            ->withConsecutive(
                [LitteralisRecordEnum::COUNT_TOTAL_FEATURES->value, 10],
                [LitteralisRecordEnum::COUNT_MATCHING_FEATURES->value, 4],
                [LitteralisRecordEnum::COUNT_EXTRACTED_FEATURES->value, 3],
            );

        $this->reporter
            ->expects(self::once())
            ->method('addWarning')
            ->with(LitteralisRecordEnum::WARNING_MISSING_GEOMETRY->value, [
                CommonRecordEnum::ATTR_REGULATION_ID->value => 'arrete3',
                CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                CommonRecordEnum::ATTR_DETAILS->value => [
                    'idemprise' => 'emprise4',
                ],
            ]);

        $extractor = new LitteralisExtractor($this->clientFactory);
        $extractor->configure($this->enabledOrgs, $this->credentials);

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

        $this->assertEquals($expectedResult, $extractor->extractFeaturesByRegulation('test', $laterThan, $this->reporter));
    }

    public function testErrorClientNotRegistered(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organization with name "other" is not enabled');

        $extractor = new LitteralisExtractor($this->clientFactory);
        $extractor->configure($this->enabledOrgs, $this->credentials);

        $extractor->extractFeaturesByRegulation('other', new \DateTimeImmutable(), $this->reporter);
    }
}
