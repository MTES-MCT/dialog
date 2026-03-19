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
            // Mesure vide : doit être ignorée (continue) dans l’agrégation
            [
                'geometry' => ['type' => 'LineString'],
                'properties' => [
                    'idemprise' => 100,
                    'arretesrcid' => 'arrete1',
                    'collectiviteagenceid' => 173214,
                    'shorturl' => 'https://dl.sogelink.fr/?a',
                    'mesure' => '',
                    'parametresmesure' => '',
                    'parametresemprise' => '',
                    'localisations' => 'Rue A',
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
                [LitteralisRecordEnum::COUNT_EXTRACTED_FEATURES->value, 4, ['regulationsCount' => 2]],
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

    /**
     * Valide que le flux WFS Communication traite correctement un payload réel (Savoie).
     * Le fixture est un extrait du flux LIcommunication avec la même structure que le WFS.
     */
    public function testExtractFeaturesByRegulationWithSavoiePayload(): void
    {
        $fixturePath = __DIR__ . '/../../../../fixtures/litteralis/savoie_communication_sample.json';
        self::assertFileExists($fixturePath, 'Fixture Savoie manquant : exécuter depuis la racine du projet avec le fichier savoie.json présent pour régénérer le sample.');

        $payload = json_decode((string) file_get_contents($fixturePath), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('features', $payload);

        $features = $payload['features'];
        self::assertNotEmpty($features, 'Le fixture doit contenir au moins une feature');

        $laterThan = new \DateTimeImmutable('2026-01-01');

        $this->enabledOrgs = ['savoie'];
        $this->credentials = (new LitteralisCredentials())
            ->add('savoie', 'uuid-savoie', 'password-savoie');

        $this->clientFactory
            ->expects(self::once())
            ->method('create')
            ->with('password-savoie')
            ->willReturn($this->client);

        $expectedDate = $laterThan->format(\DateTimeInterface::ISO8601);
        $cqlFilter = "(mesure ILIKE '%circulation interdite%' OR mesure ILIKE '%limitation de vitesse%' OR mesure ILIKE '%interruption de circulation%' OR mesure ILIKE '%interdiction de stationnement%') AND (arretefin IS NULL OR arretefin >= '" . $expectedDate . "')";

        $this->client
            ->expects(self::exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(\count($features), \count($features));

        $this->client
            ->expects(self::once())
            ->method('fetchAllPaginated')
            ->with($cqlFilter, $this->reporter)
            ->willReturn($features);

        $this->reporter->expects(self::any())->method('addCount');
        $this->reporter->expects(self::any())->method('addWarning');
        $this->reporter->expects(self::any())->method('onExtract');

        $extractor = new LitteralisCommunicationExtractor($this->clientFactory);
        $extractor->configure($this->enabledOrgs, $this->credentials);

        $result = $extractor->extractFeaturesByRegulation('savoie', $laterThan, $this->reporter);

        self::assertNotEmpty($result, 'L’extracteur doit produire au moins une régulation à partir du payload Savoie');

        foreach ($result as $identifier => $regulationFeatures) {
            self::assertIsString($identifier);
            self::assertStringContainsString('#', $identifier, 'Clé attendue : collectiviteagenceid#arretesrcid');
            self::assertIsArray($regulationFeatures);
            self::assertNotEmpty($regulationFeatures);

            foreach ($regulationFeatures as $synthFeature) {
                self::assertArrayHasKey('geometry', $synthFeature);
                self::assertArrayHasKey('properties', $synthFeature);
                self::assertArrayHasKey('crs', $synthFeature['geometry'], 'La géométrie doit avoir un CRS EPSG:4326');
                self::assertSame('EPSG:4326', $synthFeature['geometry']['crs']['properties']['name'] ?? null);
                self::assertArrayHasKey('mesures', $synthFeature['properties'], 'Feature synthétique doit exposer "mesures" pour le transformer');
                self::assertArrayHasKey('parametresmesures', $synthFeature['properties']);
                self::assertArrayHasKey('arretesrcid', $synthFeature['properties']);
            }
        }

        // Vérifier la présence d’au moins une régulation Savoie connue (collectiviteagenceid 173483)
        $savoieKeys = array_filter(array_keys($result), fn (string $k) => str_starts_with($k, '173483#'));
        self::assertNotEmpty($savoieKeys, 'Au moins une entrée doit correspondre au Département de la Savoie (173483)');
    }
}
