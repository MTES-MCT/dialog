<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\GeocoderInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\EudonetParis\EudonetParisLocationItem;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Organization;
use App\Infrastructure\EudonetParis\EudonetParisExtractor;
use App\Infrastructure\EudonetParis\EudonetParisTransformer;
use App\Infrastructure\EudonetParis\EudonetParisTransformerResult;
use PHPUnit\Framework\TestCase;

final class EudonetParisTransformerTest extends TestCase
{
    private $geocoder;

    protected function setUp(): void
    {
        $this->geocoder = $this->createMock(GeocoderInterface::class);
    }

    public function testTransform(): void
    {
        $roadName = 'Rue Eugène Berthoud';
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/06/05 14:30:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => str_repeat('a', 256),
            ],
            'measures' => [
                [
                    'fields' => [
                        EudonetParisExtractor::MESURE_ID => 'mesure1',
                        EudonetParisExtractor::MESURE_NOM => 'circulation interdite',
                    ],
                    'locations' => [
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'La totalité de la voie',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => $roadName,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = '20230514-1';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::OTHER->value;
        $generalInfoCommand->otherCategoryText = 'Temporaire';
        $generalInfoCommand->description = str_repeat('a', 255);
        $generalInfoCommand->organization = $organization;
        $generalInfoCommand->startDate = new \DateTimeImmutable('2023-06-05 14:30:00 Europe/Paris');
        $generalInfoCommand->endDate = new \DateTimeImmutable('2023-07-12 18:00:00 Europe/Paris');

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;
        $measureCommand->vehicleSet = $vehicleSet;

        $locationItem = new EudonetParisLocationItem();
        $locationItem->roadName = $roadName;
        $locationItem->fromHouseNumber = null;
        $locationItem->toHouseNumber = null;
        $locationItem->geometry = null;
        $locationItem->measures = [$measureCommand];

        $importCommand = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$locationItem]);
        $result = new EudonetParisTransformerResult($importCommand, []);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder);

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    private function provideTransformHouseNumberAndJunction(): array
    {
        return [
            [
                'porteSur' => 'Une section',
            ],
            [
                'porteSur' => 'Une zone',
            ],
            [
                'porteSur' => 'Un axe',
            ],
        ];
    }

    /**
     * @dataProvider provideTransformHouseNumberAndJunction
     */
    public function testTransformHouseNumberAndJunction(string $porteSur): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/06/05 14:30:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => 'Description',
            ],
            'measures' => [
                [
                    'fields' => [
                        EudonetParisExtractor::MESURE_ID => 'mesure1',
                        EudonetParisExtractor::MESURE_NOM => 'circulation interdite',
                    ],
                    'locations' => [
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => $porteSur,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => 'Rue Eugène Berthoud',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => 'Rue Jean Perrin',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => '26',
                            ],
                        ],
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => $porteSur,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => 'Rue Eugène Berthoud',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => 'Rue Adrien Lesesne',
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => '15',
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;
        $measureCommand->vehicleSet = $vehicleSet;

        $rueEugeneBerthoudXRueJeanPerrin = Coordinates::fromLonLat(2.3453101, 48.9062362);
        $rueEugeneBerthoud26 = Coordinates::fromLonLat(2.3453431, 48.9062625);
        $locationItem1 = new EudonetParisLocationItem();
        $locationItem1->roadName = 'Rue Eugène Berthoud';
        $locationItem1->fromHouseNumber = null;
        $locationItem1->toHouseNumber = '26';
        $locationItem1->geometry = GeoJSON::toLineString([
            $rueEugeneBerthoudXRueJeanPerrin,
            $rueEugeneBerthoud26,
        ]);
        $locationItem1->measures = [$measureCommand];

        $rueEugeneBerthoud15 = Coordinates::fromLonLat(2.3453412, 48.9062610);
        $rueEugeneBerthoudXRueAdrienLesesne = Coordinates::fromLonLat(2.34944, 48.9045598);
        $locationItem2 = new EudonetParisLocationItem();
        $locationItem2->roadName = 'Rue Eugène Berthoud';
        $locationItem2->fromHouseNumber = '15';
        $locationItem2->toHouseNumber = null;
        $locationItem2->geometry = GeoJSON::toLineString([
            $rueEugeneBerthoud15,
            $rueEugeneBerthoudXRueAdrienLesesne,
        ]);
        $locationItem2->measures = [$measureCommand];

        $matcher = self::exactly(2);
        $this->geocoder
            ->expects($matcher)
            ->method('computeJunctionCoordinates')
            ->willReturnCallback(fn ($address, $roadName) => match ($matcher->getInvocationCount()) {
                1 => $this->assertEquals(['Rue Eugène Berthoud', 'Rue Jean Perrin'], [$address, $roadName]) ?: $rueEugeneBerthoudXRueJeanPerrin,
                2 => $this->assertEquals(['Rue Eugène Berthoud', 'Rue Adrien Lesesne'], [$address, $roadName]) ?: $rueEugeneBerthoudXRueAdrienLesesne,
            });

        $matcher = self::exactly(2);
        $this->geocoder
            ->expects($matcher)
            ->method('computeCoordinates')
            ->willReturnCallback(fn ($address) => match ($matcher->getInvocationCount()) {
                1 => $this->assertEquals('26 Rue Eugène Berthoud', $address) ?: $rueEugeneBerthoud26,
                2 => $this->assertEquals('15 Rue Eugène Berthoud', $address) ?: $rueEugeneBerthoud15,
            });

        $transformer = new EudonetParisTransformer($this->geocoder);

        $result = $transformer->transform($record, $organization);

        $this->assertEquals([$locationItem1, $locationItem2], $result->command->locationItems);
    }

    public function testSkipNoMeasures(): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/06/05 14:30:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => 'Description',
            ],
            'measures' => [],
        ];

        $result = new EudonetParisTransformerResult(null, [
            [
                'loc' => ['regulation_identifier' => '20230514-1'],
                'reason' => 'no_measures_found',
                'impact' => 'skip_regulation',
            ],
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder);

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    public function testSkipMeasureTypeUnknown(): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/06/05 14:30:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => 'Description',
            ],
            'measures' => [
                [
                    'fields' => [
                        EudonetParisExtractor::MESURE_ID => 'mesure1',
                        EudonetParisExtractor::MESURE_NOM => 'interdiction de stationner',
                    ],
                ],
            ],
        ];

        $result = new EudonetParisTransformerResult(null, [
            [
                'loc' => ['regulation_identifier' => '20230514-1', 'measure_id' => 'mesure1', 'fieldname' => 'NOM'],
                'impact' => 'skip_measure',
                'reason' => 'value_not_in_enum',
                'value' => 'interdiction de stationner',
                'enum' => ['circulation interdite'],
            ],
            [
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_regulation',
                'reason' => 'no_locations_gathered',
            ],
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder);

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    public function testSkipUnknownPorteSur(): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/06/05 14:30:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => 'Description',
            ],
            'measures' => [
                [
                    'fields' => [
                        EudonetParisExtractor::MESURE_ID => 'mesure1',
                        EudonetParisExtractor::MESURE_NOM => 'circulation interdite',
                    ],
                    'locations' => [
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'Autre chose',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => '...',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = new EudonetParisTransformerResult(null, [
            [
                'loc' => ['regulation_identifier' => '20230514-1', 'location_id' => 'localisation1'],
                'impact' => 'skip_location',
                'reason' => 'unsupported_location_fieldset',
                'location_raw' => '{"fields":{"2701":"localisation1","2705":"Autre chose","2710":"...","2730":null,"2740":null,"2720":null,"2737":null}}',
            ],
            [
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_regulation',
                'reason' => 'no_locations_gathered',
            ],
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder);

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    public function testSkipStartWithoutEndOrConversely(): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/06/05 14:30:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => 'Description',
            ],
            'measures' => [
                [
                    'fields' => [
                        EudonetParisExtractor::MESURE_ID => 'mesure1',
                        EudonetParisExtractor::MESURE_NOM => 'circulation interdite',
                    ],
                    'locations' => [
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'Une section',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => '...',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => 'Start road',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => null,
                            ],
                        ],
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'Une section',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => '...',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => 'End road',
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => null,
                            ],
                        ],
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'Une section',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => '...',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => 'Start house number',
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => null,
                            ],
                        ],
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'Une section',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => '...',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => null,
                                EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => 'End house number',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = new EudonetParisTransformerResult(null, [
            [
                'loc' => ['regulation_identifier' => '20230514-1', 'location_id' => 'localisation1'],
                'impact' => 'skip_location',
                'reason' => 'unsupported_location_fieldset',
                'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2710":"...","2730":"Start road","2740":null,"2720":null,"2737":null}}',
            ],
            [
                'loc' => ['regulation_identifier' => '20230514-1', 'location_id' => 'localisation1'],
                'impact' => 'skip_location',
                'reason' => 'unsupported_location_fieldset',
                'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2710":"...","2730":null,"2740":"End road","2720":null,"2737":null}}',
            ],
            [
                'loc' => ['regulation_identifier' => '20230514-1', 'location_id' => 'localisation1'],
                'impact' => 'skip_location',
                'reason' => 'unsupported_location_fieldset',
                'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2710":"...","2730":null,"2740":null,"2720":"Start house number","2737":null}}',
            ],
            [
                'loc' => ['regulation_identifier' => '20230514-1', 'location_id' => 'localisation1'],
                'impact' => 'skip_location',
                'reason' => 'unsupported_location_fieldset',
                'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2710":"...","2730":null,"2740":null,"2720":null,"2737":"End house number"}}',
            ],
            [
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_regulation',
                'reason' => 'no_locations_gathered',
            ],
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder);

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }
}
