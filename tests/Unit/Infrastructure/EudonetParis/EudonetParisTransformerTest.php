<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Organization;
use App\Infrastructure\EudonetParis\EudonetParisExtractor;
use App\Infrastructure\EudonetParis\EudonetParisTransformer;
use App\Infrastructure\EudonetParis\EudonetParisTransformerResult;
use PHPUnit\Framework\TestCase;

final class EudonetParisTransformerTest extends TestCase
{
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
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

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = 'lane';
        $locationCommand->cityCode = '75118';
        $locationCommand->cityLabel = 'Paris';
        $locationCommand->roadName = $roadName;
        $locationCommand->fromHouseNumber = null;
        $locationCommand->toHouseNumber = null;
        $locationCommand->geometry = null;

        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand->locations[] = $locationCommand;
        $measureCommand->vehicleSet = $vehicleSet;

        $importCommand = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$measureCommand]);
        $result = new EudonetParisTransformerResult($importCommand, []);

        $transformer = new EudonetParisTransformer();

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    private function provideDateParsing(): array
    {
        return [
            'standard' => ['2023/12/14 00:00:00'],
            'no-datetime' => ['2023/12/14'],
            'dmy-no-datetime' => ['14/12/2023'],
        ];
    }

    /** @dataProvider provideDateParsing */
    public function testDateParsing($startDateValue): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => $startDateValue,
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => 'Rue Eugène Berthoud',
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

        $transformer = new EudonetParisTransformer();
        $result = $transformer->transform($record, $organization);

        $this->assertEquals(
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-12-14 00:00:00', new \DateTimeZone('Europe/Paris')),
            $result->command->generalInfoCommand->startDate,
        );
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

        $transformer = new EudonetParisTransformer();

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
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_measure',
                'reason' => 'measure_errors',
                'errors' => [
                    [
                        'loc' => ['measure_id' => 'mesure1', 'fieldname' => 'NOM'],
                        'reason' => 'value_not_in_enum',
                        'value' => 'interdiction de stationner',
                        'enum' => ['circulation interdite'],
                    ],
                ],
            ],
        ]);

        $transformer = new EudonetParisTransformer();

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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
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
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_measure',
                'reason' => 'measure_errors',
                'errors' => [
                    [
                        'loc' => ['measure_id' => 'mesure1', 'location_id' => 'localisation1'],
                        'impact' => 'skip_location',
                        'reason' => 'unsupported_location_fieldset',
                        'location_raw' => '{"fields":{"2701":"localisation1","2705":"Autre chose","2708":"18\u00e8me Arrondissement","2710":"...","2730":null,"2740":null,"2720":null,"2737":null}}',
                    ],
                    [
                        'loc' => ['measure_id' => 'mesure1', 'fieldname' => 'locations'],
                        'impact' => 'skip_measure',
                        'reason' => 'no_locations_gathered',
                    ],
                ],
            ],
        ]);

        $transformer = new EudonetParisTransformer();

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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème Arrondissement',
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
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_measure',
                'reason' => 'measure_errors',
                'errors' => [
                    [
                        'loc' => ['measure_id' => 'mesure1', 'location_id' => 'localisation1'],
                        'impact' => 'skip_location',
                        'reason' => 'unsupported_location_fieldset',
                        'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2708":"18\u00e8me Arrondissement","2710":"...","2730":"Start road","2740":null,"2720":null,"2737":null}}',
                    ],
                    [
                        'loc' => ['measure_id' => 'mesure1', 'location_id' => 'localisation1'],
                        'impact' => 'skip_location',
                        'reason' => 'unsupported_location_fieldset',
                        'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2708":"18\u00e8me Arrondissement","2710":"...","2730":null,"2740":"End road","2720":null,"2737":null}}',
                    ],
                    [
                        'loc' => ['measure_id' => 'mesure1', 'location_id' => 'localisation1'],
                        'impact' => 'skip_location',
                        'reason' => 'unsupported_location_fieldset',
                        'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2708":"18\u00e8me Arrondissement","2710":"...","2730":null,"2740":null,"2720":"Start house number","2737":null}}',
                    ],
                    [
                        'loc' => ['measure_id' => 'mesure1', 'location_id' => 'localisation1'],
                        'impact' => 'skip_location',
                        'reason' => 'unsupported_location_fieldset',
                        'location_raw' => '{"fields":{"2701":"localisation1","2705":"Une section","2708":"18\u00e8me Arrondissement","2710":"...","2730":null,"2740":null,"2720":null,"2737":"End house number"}}',
                    ],
                    [
                        'loc' => ['measure_id' => 'mesure1', 'fieldname' => 'locations'],
                        'impact' => 'skip_measure',
                        'reason' => 'no_locations_gathered',
                    ],
                ],
            ],
        ]);

        $transformer = new EudonetParisTransformer();

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    public function testSkipInvalidArrondissement(): void
    {
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => 'invalid',
                                EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => 'Rue Eugène Berthoud',
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

        $errors = [
            [
                'loc' => ['regulation_identifier' => '20230514-1'],
                'impact' => 'skip_measure',
                'reason' => 'measure_errors',
                'errors' => [
                    [
                        'loc' => ['measure_id' => 'mesure1', 'location_id' => 'localisation1', 'fieldname' => 'ARRONDISSEMENT'],
                        'reason' => 'value_does_not_match_pattern',
                        'impact' => 'skip_location',
                        'value' => 'invalid',
                        'pattern' => '/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i',
                    ],
                    [
                        'loc' => ['measure_id' => 'mesure1', 'fieldname' => 'locations'],
                        'reason' => 'no_locations_gathered',
                        'impact' => 'skip_measure',
                    ],
                ],
            ],
        ];

        $result = new EudonetParisTransformerResult(null, $errors);

        $transformer = new EudonetParisTransformer();

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    public function testSkipInvalidStartDate(): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => 'invalid',
                EudonetParisExtractor::ARRETE_DATE_FIN => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => str_repeat('a', 256),
            ],
            'measures' => ['...'],
        ];

        $transformer = new EudonetParisTransformer();
        $result = new EudonetParisTransformerResult(
            null,
            [
                [
                    'loc' => ['fieldname' => 'ARRETE_DATE_DEBUT'],
                    'impact' => 'skip_regulation',
                    'reason' => 'parsing_failed',
                    'value' => 'invalid',
                ],
            ],
        );

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    public function testSkipInvalidEndDate(): void
    {
        $organization = $this->createMock(Organization::class);

        $record = [
            'fields' => [
                EudonetParisExtractor::ARRETE_ID => '20230514-1',
                EudonetParisExtractor::ARRETE_DATE_DEBUT => '2023/07/12 18:00:00',
                EudonetParisExtractor::ARRETE_DATE_FIN => 'invalid',
                EudonetParisExtractor::ARRETE_TYPE => 'Temporaire',
                EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE => str_repeat('a', 256),
            ],
            'measures' => ['...'],
        ];

        $transformer = new EudonetParisTransformer();
        $result = new EudonetParisTransformerResult(
            null,
            [
                [
                    'loc' => ['fieldname' => 'ARRETE_DATE_FIN'],
                    'impact' => 'skip_regulation',
                    'reason' => 'parsing_failed',
                    'value' => 'invalid',
                ],
            ],
        );

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }
}
