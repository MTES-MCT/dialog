<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\Litteralis;

use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\Integration\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\Litteralis\LitteralisClient;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationClientFactory;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationExtractor;
use App\Infrastructure\Integration\Litteralis\LitteralisRecordEnum;
use PHPUnit\Framework\TestCase;

final class LitteralisCommunicationExtractorTest extends TestCase
{
    private array $enabledOrgs;
    private LitteralisCredentials $credentials;
    private $clientFactory;
    private $client;
    private $reporter;

    protected function setUp(): void
    {
        $this->enabledOrgs = ['test'];
        $this->credentials = (new LitteralisCredentials())
            ->add('test', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'testpassword');

        $this->clientFactory = $this->createMock(LitteralisCommunicationClientFactory::class);
        $this->client = $this->createMock(LitteralisClient::class);
        $this->reporter = $this->createMock(Reporter::class);
    }

    public function testExtractFeaturesByRegulationGroupsByEmpriseAndBuildsSyntheticFeatures(): void
    {
        $laterThan = new \DateTimeImmutable('2024-08-01');

        // Flux Communication : 2 features = 2 mesures pour la même emprise (idemprise 100, arretesrcid arrete1)
        $features = [
            [
                'geometry' => ['type' => 'LineString'],
                'properties' => [
                    'idemprise' => 100,
                    'arretesrcid' => 'arrete1',
                    'collectiviteagenceid' => 173214,
                    'shorturl' => 'https://dl.sogelink.fr/?a',
                    'mesure' => 'Circulation interdite',
                    'parametresmesure' => 'jours et horaires : 8h-18h',
                    'parametresemprise' => '',
                    'localisations' => 'Rue A',
                ],
            ],
            [
                'geometry' => ['type' => 'LineString'],
                'properties' => [
                    'idemprise' => 100,
                    'arretesrcid' => 'arrete1',
                    'collectiviteagenceid' => 173214,
                    'shorturl' => 'https://dl.sogelink.fr/?a',
                    'mesure' => 'Limitation de vitesse',
                    'parametresmesure' => 'vitesse : 30',
                    'parametresemprise' => '',
                    'localisations' => 'Rue A',
                ],
            ],
            [
                'geometry' => ['type' => 'Polygon'],
                'properties' => [
                    'idemprise' => 101,
                    'arretesrcid' => 'arrete2',
                    'collectiviteagenceid' => 173214,
                    'shorturl' => 'https://dl.sogelink.fr/?b',
                    'mesure' => 'Interdiction de stationnement',
                    'parametresmesure' => '',
                    'parametresemprise' => 'dates : 01/01-31/01',
                    'localisations' => 'Rue B',
                ],
            ],
            [
                'properties' => [
                    'idemprise' => 99,
                    'arretesrcid' => 'arrete0',
                    'shorturl' => 'https://dl.sogelink.fr/?c',
                ],
            ],
        ];

        $this->clientFactory
            ->expects(self::once())
            ->method('create')
            ->with('testpassword')
            ->willReturn($this->client);

        $expectedDate = $laterThan->format(\DateTimeInterface::ISO8601);
        $cqlFilter = "(mesure ILIKE '%circulation interdite%' OR mesure ILIKE '%limitation de vitesse%' OR mesure ILIKE '%interruption de circulation%' OR mesure ILIKE '%interdiction de stationnement%') AND (arretefin IS NULL OR arretefin >= '" . $expectedDate . "')";

        $this->client
            ->expects(self::exactly(2))
            ->method('count')
            ->withConsecutive(
                [null, $this->reporter],
                [$cqlFilter, $this->reporter],
            )
            ->willReturnOnConsecutiveCalls(10, 3);

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
                [LitteralisRecordEnum::COUNT_MATCHING_FEATURES->value, 3],
                [LitteralisRecordEnum::COUNT_EXTRACTED_FEATURES->value, 3, ['regulationsCount' => 2]],
            );

        $this->reporter
            ->expects(self::once())
            ->method('addWarning')
            ->with(LitteralisRecordEnum::WARNING_MISSING_GEOMETRY->value, [
                CommonRecordEnum::ATTR_REGULATION_ID->value => 'arrete0',
                CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?c',
                CommonRecordEnum::ATTR_DETAILS->value => ['idemprise' => 99],
            ]);

        $extractor = new LitteralisCommunicationExtractor($this->clientFactory);
        $extractor->configure($this->enabledOrgs, $this->credentials);

        $crs = [
            'type' => 'name',
            'properties' => ['name' => 'EPSG:4326'],
        ];

        $expectedResult = [
            '173214#arrete1' => [
                [
                    'geometry' => ['type' => 'LineString', 'crs' => $crs],
                    'properties' => [
                        'idemprise' => 100,
                        'arretesrcid' => 'arrete1',
                        'collectiviteagenceid' => 173214,
                        'shorturl' => 'https://dl.sogelink.fr/?a',
                        'mesure' => 'Circulation interdite',
                        'parametresmesure' => 'jours et horaires : 8h-18h',
                        'parametresemprise' => '',
                        'localisations' => 'Rue A',
                        'mesures' => 'Circulation interdite;Limitation de vitesse',
                        'parametresmesures' => 'Circulation interdite | jours et horaires : 8h-18h ; Limitation de vitesse | vitesse : 30',
                    ],
                ],
            ],
            '173214#arrete2' => [
                [
                    'geometry' => ['type' => 'Polygon', 'crs' => $crs],
                    'properties' => [
                        'idemprise' => 101,
                        'arretesrcid' => 'arrete2',
                        'collectiviteagenceid' => 173214,
                        'shorturl' => 'https://dl.sogelink.fr/?b',
                        'mesure' => 'Interdiction de stationnement',
                        'parametresmesure' => '',
                        'parametresemprise' => 'dates : 01/01-31/01',
                        'localisations' => 'Rue B',
                        'mesures' => 'Interdiction de stationnement',
                        'parametresmesures' => '',
                    ],
                ],
            ],
        ];

        $this->reporter
            ->expects(self::once())
            ->method('onExtract')
            ->with(self::callback(function (string $json) use ($expectedResult): bool {
                $decoded = json_decode($json, true);

                return $decoded === $expectedResult;
            }));

        $result = $extractor->extractFeaturesByRegulation('test', $laterThan, $this->reporter);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetClientThrowsWhenOrganizationNotEnabled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organization with name "other" is not enabled for Communication flux');

        $extractor = new LitteralisCommunicationExtractor($this->clientFactory);
        $extractor->configure($this->enabledOrgs, $this->credentials);

        $extractor->extractFeaturesByRegulation('other', new \DateTimeImmutable(), $this->reporter);
    }
}
