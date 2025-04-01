<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\BacIdf;

use App\Application\CommandBusInterface;
use App\Application\Integration\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\User\Organization;
use App\Infrastructure\Integration\BacIdf\BacIdfCityProcessorInterface;
use App\Infrastructure\Integration\BacIdf\BacIdfTransformer;
use App\Infrastructure\Integration\BacIdf\BacIdfTransformerResult;
use PHPUnit\Framework\TestCase;

final class BacIdfTransformerTest extends TestCase
{
    private $commandBus;
    private $organization;
    private $cityProcessor;
    private $cityCode = '93027';
    private $siret = '21930027400012';

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->cityProcessor = $this->createMock(BacIdfCityProcessorInterface::class);
    }

    public function testTransform(): void
    {
        $this->cityProcessor
            ->expects(self::once())
            ->method('getSiretFromInseeCode')
            ->willReturn($this->siret);

        $organizationView = new GetOrCreateOrganizationView($this->organization, false);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrCreateOrganizationBySiretCommand($this->siret))
            ->willReturn($organizationView);

        $record = json_decode(file_get_contents(__DIR__ . '/data/decree1.json'), associative: true);

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = '15.248-circ';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfoCommand->title = 'Circulation passage Pierre Curie';

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet = new SaveNamedStreetCommand();
        $locationCommand->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet->cityCode = $this->cityCode;
        $locationCommand->namedStreet->cityLabel = 'La Courneuve (93120)';
        $locationCommand->namedStreet->roadName = 'Passage Pierre Curie';
        $locationCommand->namedStreet->fromHouseNumber = null;
        $locationCommand->namedStreet->toHouseNumber = null;
        $locationCommand->namedStreet->geometry = json_encode(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [2.3844267, 48.9207082],
                    [2.3844462, 48.9207571],
                    [2.3847146, 48.9214287],
                    [2.3847305, 48.9214627],
                ],
            ],
        );

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand->locations = [$locationCommand];
        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = false;
        $vehicleSet->restrictedTypes = [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value];
        $vehicleSet->heavyweightMaxWeight = 3.5;
        $vehicleSet->exemptedTypes = [VehicleTypeEnum::EMERGENCY_SERVICES->value, VehicleTypeEnum::OTHER->value];
        $vehicleSet->otherExemptedTypeText = "Véhicules de déménagement justifiant d'une dérogation, véhicules de services";
        $measureCommand->vehicleSet = $vehicleSet;
        $periodCommand = new SavePeriodCommand();
        $periodCommand->isPermanent = true;
        $periodCommand->startDate = new \DateTimeImmutable('2015-08-17T00:00:00Z');
        $periodCommand->startTime = new \DateTimeImmutable('2015-08-17T00:00:00Z');
        $periodCommand->endDate = null;
        $periodCommand->endTime = null;
        $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $periodCommand->timeSlots = [];
        $measureCommand->periods[] = $periodCommand;

        $importCommand = new ImportBacIdfRegulationCommand($generalInfoCommand, [$measureCommand]);
        $result = new BacIdfTransformerResult($importCommand, [], $this->organization);

        $transformer = new BacIdfTransformer($this->commandBus, $this->cityProcessor);

        $this->assertEquals($result, $transformer->transform($record));
    }

    public function testTransformMinimal(): void
    {
        $this->cityProcessor
            ->expects(self::once())
            ->method('getSiretFromInseeCode')
            ->willReturn($this->siret);

        $organizationView = new GetOrCreateOrganizationView($this->organization, false);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrCreateOrganizationBySiretCommand($this->siret))
            ->willReturn($organizationView);

        $record = [
            'ARR_REF' => 'arr_1',
            'ARR_NOM' => 'nom_1',
            'ARR_COMMUNE' => [
                'ARR_INSEE' => $this->cityCode,
                'ARR_VILLE' => 'La Courneuve',
                'ARR_CODE_POSTAL' => '93120',
            ],
            'REG_TYPE' => 'CIRCULATION',
            'REG_CIRCULATION' => [
                [
                    'CIRC_REG' => [
                        'REG_VOIES' => [
                            [
                                'VOIE_NAME' => 'Ruelle du Pressin',
                                'VOIE_GEOJSON' => [
                                    'features' => [
                                        [
                                            'geometry' => [
                                                'type' => 'LineString',
                                                'coordinates' => [
                                                    [2.3820543, 48.9220036],
                                                    [2.3821628, 48.9221769],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'REG_RESTRICTION' => true,
                        'PERIODE_JH' => [],
                    ],
                ],
            ],
            'ARR_DUREE' => [
                'ARR_TEMPORALITE' => 'PERMANENT',
                'PERIODE_DEBUT' => [
                    '$date' => '2024-02-06T17:25:00Z',
                ],
            ],
        ];

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = 'arr_1';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfoCommand->title = 'nom_1';

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet = new SaveNamedStreetCommand();
        $locationCommand->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet->cityCode = $this->cityCode;
        $locationCommand->namedStreet->cityLabel = 'La Courneuve (93120)';
        $locationCommand->namedStreet->roadName = 'Ruelle du Pressin';
        $locationCommand->namedStreet->fromHouseNumber = null;
        $locationCommand->namedStreet->toHouseNumber = null;
        $locationCommand->namedStreet->geometry = json_encode(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [2.3820543, 48.9220036],
                    [2.3821628, 48.9221769],
                ],
            ],
        );

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand->locations = [$locationCommand];
        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;
        $measureCommand->vehicleSet = $vehicleSet;

        $importCommand = new ImportBacIdfRegulationCommand($generalInfoCommand, [$measureCommand]);
        $result = new BacIdfTransformerResult($importCommand, [], $this->organization);

        $transformer = new BacIdfTransformer($this->commandBus, $this->cityProcessor);

        $this->assertEquals($result, $transformer->transform($record));
    }

    private function provideTransformErrors(): array
    {
        return [
            'basiccheck-reg_type' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_TYPE',
                        ],
                        'reason' => 'value_absent',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'basiccheck-circulation' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'REG_TYPE' => 'NOT_CIRCULATION',
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_TYPE',
                        ],
                        'reason' => 'value_not_expected',
                        'value' => 'NOT_CIRCULATION',
                        'expected' => 'CIRCULATION',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'basiccheck-fullcity-1' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [],
                        ],
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_CIRCULATION.0.CIRC_REG.REG_VOIES',
                        ],
                        'reason' => 'value_absent',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'basiccheck-fullcity2' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [
                                'REG_VOIES' => [
                                    [],
                                ],
                            ],
                        ],
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_CIRCULATION.0.CIRC_REG.REG_VOIES.VOIE_GEOJSON',
                        ],
                        'reason' => 'value_absent',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'basiccheck-permanent' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [
                                'REG_VOIES' => [
                                    [
                                        'VOIE_GEOJSON' => '...',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ARR_DUREE' => [
                        'ARR_TEMPORALITE' => 'TEMPORAIRE',
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'ARR_DUREE.ARR_TEMPORALITE',
                        ],
                        'reason' => 'value_not_expected',
                        'value' => 'TEMPORAIRE',
                        'expected' => 'PERMANENT',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'generalinfo-date-not-string' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'ARR_NOM' => 'nom_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [
                                'REG_VOIES' => [
                                    [
                                        'VOIE_GEOJSON' => '...',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ARR_DUREE' => [
                        'ARR_TEMPORALITE' => 'PERMANENT',
                        'PERIODE_DEBUT' => [
                            '$date' => 1707236588,
                        ],
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'ARR_DUREE.PERIODE_DEBUT.$date',
                        ],
                        'reason' => 'value_not_expected_type',
                        'value' => '1707236588',
                        'expected_type' => 'string',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'regRestriction-absent' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'ARR_NOM' => 'nom_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [
                                'REG_VOIES' => [
                                    [
                                        'VOIE_GEOJSON' => '...',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ARR_DUREE' => [
                        'ARR_TEMPORALITE' => 'PERMANENT',
                        'PERIODE_DEBUT' => [
                            '$date' => '2024-02-06T17:25:00Z',
                        ],
                    ],
                    'ARR_COMMUNE' => [
                        'ARR_INSEE' => $this->cityCode,
                        'ARR_VILLE' => 'La Courneuve',
                        'ARR_CODE_POSTAL' => '93120',
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_CIRCULATION.0.CIRC_REG.REG_RESTRICTION',
                        ],
                        'reason' => 'value_absent',
                        'impact' => 'skip_measure',
                    ],
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                        ],
                        'reason' => 'no_measures_found',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'regRestriction-false' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'ARR_NOM' => 'nom_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [
                                'REG_VOIES' => [
                                    [
                                        'VOIE_GEOJSON' => '...',
                                    ],
                                ],
                                'REG_RESTRICTION' => false,
                            ],
                        ],
                    ],
                    'ARR_DUREE' => [
                        'ARR_TEMPORALITE' => 'PERMANENT',
                        'PERIODE_DEBUT' => [
                            '$date' => '2024-02-06T17:25:00Z',
                        ],
                    ],
                    'ARR_COMMUNE' => [
                        'ARR_INSEE' => $this->cityCode,
                        'ARR_VILLE' => 'La Courneuve',
                        'ARR_CODE_POSTAL' => '93120',
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_CIRCULATION.0.CIRC_REG.REG_RESTRICTION',
                        ],
                        'reason' => 'value_not_expected',
                        'expected' => true,
                        'value' => false,
                        'impact' => 'skip_measure',
                    ],
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                        ],
                        'reason' => 'no_measures_found',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
            'features-empty' => [
                'record' => [
                    'ARR_REF' => 'arr_1',
                    'ARR_NOM' => 'nom_1',
                    'REG_TYPE' => 'CIRCULATION',
                    'REG_CIRCULATION' => [
                        [
                            'CIRC_REG' => [
                                'REG_VOIES' => [
                                    [
                                        'VOIE_GEOJSON' => [
                                            'features' => [],
                                        ],
                                    ],
                                ],
                                'REG_RESTRICTION' => true,
                                'PERIODE_JH' => [],
                            ],
                        ],
                    ],
                    'ARR_DUREE' => [
                        'ARR_TEMPORALITE' => 'PERMANENT',
                        'PERIODE_DEBUT' => [
                            '$date' => '2024-02-06T17:25:00Z',
                        ],
                    ],
                    'ARR_COMMUNE' => [
                        'ARR_INSEE' => $this->cityCode,
                        'ARR_VILLE' => 'La Courneuve',
                        'ARR_CODE_POSTAL' => '93120',
                    ],
                ],
                'errors' => [
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'REG_CIRCULATION.0.CIRC_REG.REG_VOIES.0.VOIE_GEOJSON.features',
                        ],
                        'reason' => 'array_empty',
                        'impact' => 'skip_location',
                    ],
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                            'fieldname' => 'measures.0',
                        ],
                        'reason' => 'no_locations_gathered',
                        'impact' => 'skip_measure',
                    ],
                    [
                        'loc' => [
                            'regulation_identifier' => 'arr_1',
                        ],
                        'reason' => 'no_measures_found',
                        'impact' => 'skip_regulation',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTransformErrors
     */
    public function testTransformErrors(array $record, array $errors): void
    {
        $this->cityProcessor
            ->method('getSiretFromInseeCode')
            ->willReturn($this->siret);

        if (\array_key_exists('ARR_COMMUNE', $record)) {
            $organizationView = new GetOrCreateOrganizationView($this->organization, false);

            $this->commandBus
                ->expects(self::once())
                ->method('handle')
                ->with(new GetOrCreateOrganizationBySiretCommand($this->siret))
                ->willReturn($organizationView);
        } else {
            $this->commandBus
                ->expects(self::never())
                ->method('handle');
        }

        $transformer = new BacIdfTransformer($this->commandBus, $this->cityProcessor);

        $result = $transformer->transform($record);

        $this->assertNull($result->command);
        $this->assertEquals(
            $errors,
            array_map(fn ($error) => array_diff_key($error, ['explaination' => '']), $result->errors),
        );
    }

    private function doTestTransform(callable $callback): void
    {
        $this->cityProcessor
            ->expects(self::once())
            ->method('getSiretFromInseeCode')
            ->willReturn($this->siret);

        $organizationView = new GetOrCreateOrganizationView($this->organization, false);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrCreateOrganizationBySiretCommand($this->siret))
            ->willReturn($organizationView);

        $regCirculation = [
            'CIRC_REG' => [
                'REG_VOIES' => [
                    [
                        'VOIE_NAME' => 'Ruelle du Pressin',
                        'VOIE_GEOJSON' => [
                            'features' => [
                                [
                                    'geometry' => [
                                        'type' => 'LineString',
                                        'coordinates' => [
                                            [2.3820543, 48.9220036],
                                            [2.3821628, 48.9221769],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'REG_RESTRICTION' => true,
                'PERIODE_JH' => [],
            ],
        ];

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = 'arr_1';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfoCommand->title = 'nom_1';

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet = new SaveNamedStreetCommand();
        $locationCommand->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet->cityCode = $this->cityCode;
        $locationCommand->namedStreet->cityLabel = 'La Courneuve (93120)';
        $locationCommand->namedStreet->roadName = 'Ruelle du Pressin';
        $locationCommand->namedStreet->fromHouseNumber = null;
        $locationCommand->namedStreet->toHouseNumber = null;
        $locationCommand->namedStreet->geometry = json_encode(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [2.3820543, 48.9220036],
                    [2.3821628, 48.9221769],
                ],
            ],
        );

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand->locations = [$locationCommand];
        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $measureCommand->vehicleSet = $vehicleSetCommand;

        $callback($regCirculation, $measureCommand);

        $record = [
            'ARR_REF' => 'arr_1',
            'ARR_NOM' => 'nom_1',
            'ARR_COMMUNE' => [
                'ARR_INSEE' => $this->cityCode,
                'ARR_VILLE' => 'La Courneuve',
                'ARR_CODE_POSTAL' => '93120',
            ],
            'REG_TYPE' => 'CIRCULATION',
            'REG_CIRCULATION' => [
                $regCirculation,
            ],
            'ARR_DUREE' => [
                'ARR_TEMPORALITE' => 'PERMANENT',
                'PERIODE_DEBUT' => [
                    '$date' => '2024-02-06T17:25:00Z',
                ],
            ],
        ];

        $importCommand = new ImportBacIdfRegulationCommand($generalInfoCommand, [$measureCommand]);
        $result = new BacIdfTransformerResult($importCommand, [], $this->organization);

        $transformer = new BacIdfTransformer($this->commandBus, $this->cityProcessor);

        $this->assertEquals($result, $transformer->transform($record));
    }

    private function provideTransformVehicleSet(): \Iterator
    {
        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = false;
        $vehicleSetCommand->restrictedTypes = [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value];
        $vehicleSetCommand->heavyweightMaxWeight = 3.5;

        yield [
            ['VEH_POIDS' => ['VEH_PTAC_MIN' => 3.5]],
            $vehicleSetCommand,
        ];

        yield [
            ['VEH_POIDS' => ['VEH_PTAC_MAX' => 3.5]],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = false;
        $vehicleSetCommand->restrictedTypes = [VehicleTypeEnum::DIMENSIONS->value];
        $vehicleSetCommand->maxWidth = 1.5;

        yield [
            ['VEH_DIMENSION' => ['VEH_LARG_MIN' => 1.5]],
            $vehicleSetCommand,
        ];

        yield [
            ['VEH_DIMENSION' => ['VEH_LARG_MAX' => 1.5]],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = false;
        $vehicleSetCommand->restrictedTypes = [VehicleTypeEnum::DIMENSIONS->value];
        $vehicleSetCommand->maxLength = 2;

        yield [
            ['VEH_DIMENSION' => ['VEH_LONG_MIN' => 2]],
            $vehicleSetCommand,
        ];

        yield [
            ['VEH_DIMENSION' => ['VEH_LONG_MAX' => 2]],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = false;
        $vehicleSetCommand->restrictedTypes = [VehicleTypeEnum::DIMENSIONS->value];
        $vehicleSetCommand->maxHeight = 2.4;

        yield [
            ['VEH_DIMENSION' => ['VEH_HAUT_MIN' => 2.4]],
            $vehicleSetCommand,
        ];

        yield [
            ['VEH_DIMENSION' => ['VEH_HAUT_MAX' => 2.4]],
            $vehicleSetCommand,
        ];
    }

    /**
     * @dataProvider provideTransformVehicleSet
     */
    public function testTransformVehicleSet($data, SaveVehicleSetCommand $vehicleSetCommand): void
    {
        $this->doTestTransform(function (array &$regCirculation, SaveMeasureCommand &$measureCommand) use ($data, $vehicleSetCommand) {
            $regCirculation['CIRC_VEHICULES'] = $data;
            $measureCommand->vehicleSet = $vehicleSetCommand;
        });
    }

    private function provideTransformExemptedVehicleTypes(): \Iterator
    {
        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $vehicleSetCommand->exemptedTypes = [VehicleTypeEnum::OTHER->value];
        $vehicleSetCommand->otherExemptedTypeText = 'Déménagement';

        yield [
            [
                'REG_EXCEPT' => ['OTHER'],
                'REG_EXCEPT_DESC' => 'déménagement',
            ],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $vehicleSetCommand->exemptedTypes = [VehicleTypeEnum::EMERGENCY_SERVICES->value, VehicleTypeEnum::OTHER->value];
        $vehicleSetCommand->otherExemptedTypeText = 'Véhicules de services, transport de déchets, police, pompiers, déménagement';

        yield [
            [
                'REG_EXCEPT' => [
                    'SECOURS',
                    'VEHICULES DE SERVICES',
                    'TRANSPORT DE DECHETS',
                    'POLICE',
                    'POMPIERS',
                    'OTHER',
                ],
                'REG_EXCEPT_DESC' => 'déménagement',
            ],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $vehicleSetCommand->exemptedTypes = [VehicleTypeEnum::OTHER->value];
        $vehicleSetCommand->otherExemptedTypeText = 'Véhicules autorisés';

        yield [
            [
                'REG_EXCEPT' => ['OTHER'],
                'REG_EXCEPT_DESC' => 'Les véhicules de charges et de commerces dont le conducteur peut justifier sa présence [...]',
            ],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $vehicleSetCommand->exemptedTypes = [VehicleTypeEnum::OTHER->value];
        $vehicleSetCommand->otherExemptedTypeText = 'Véhicules de services, transports en commun, livraisons';

        yield [
            [
                'REG_EXCEPT' => ['OTHER'],
                'REG_EXCEPT_DESC' => "véhicules d'intervention des Services Publics de l'Etat [...]",
            ],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $vehicleSetCommand->exemptedTypes = [VehicleTypeEnum::OTHER->value];
        $vehicleSetCommand->otherExemptedTypeText = "Véhicules d'intérêt général, véhicules de démanagement, véhicules de transport de matériel de chantier";

        yield [
            [
                'REG_EXCEPT' => ['OTHER'],
                'REG_EXCEPT_DESC' => "éhicules d'intérêt général tels que définis [...]", // sic: "éhicules"
            ],
            $vehicleSetCommand,
        ];

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;
        $vehicleSetCommand->exemptedTypes = [];
        $vehicleSetCommand->otherExemptedTypeText = null;

        yield [
            [
                'REG_EXCEPT' => ['OTHER'],
                'REG_EXCEPT_DESC' => "Tous moteurs de quelque nature qu'ils soient [...]",
            ],
            $vehicleSetCommand,
        ];
    }

    /**
     * @dataProvider provideTransformExemptedVehicleTypes
     */
    public function testTransformExemptedVehicleTypes(array $data, SaveVehicleSetCommand $vehicleSetCommand): void
    {
        $this->doTestTransform(function (array &$regCirculation, SaveMeasureCommand &$measureCommand) use ($data, $vehicleSetCommand) {
            $regCirculation['CIRC_REG'] = array_merge($regCirculation['CIRC_REG'], $data);
            $measureCommand->vehicleSet = $vehicleSetCommand;
        });
    }

    private function provideTransformPeriods(): \Iterator
    {
        $periodCommand0 = new SavePeriodCommand();
        $periodCommand0->isPermanent = true;
        $periodCommand0->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand0->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand0->endDate = null;
        $periodCommand0->endTime = null;
        $periodCommand0->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $periodCommand0->timeSlots = [];

        yield [
            [
                [
                    'JOUR' => [1, 2, 3, 4, 5, 6, 0],
                ],
            ],
            [$periodCommand0],
        ];

        yield [
            [
                [
                    'JOUR' => [1, 2, 3, 4, 5, 6, 0],
                    'HEURE_DEB' => '00:00',
                    'HEURE_FIN' => '23:59',
                ],
            ],
            [$periodCommand0],
        ];

        $periodCommand1 = new SavePeriodCommand();
        $periodCommand1->isPermanent = true;
        $periodCommand1->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand1->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand1->endDate = null;
        $periodCommand1->endTime = null;
        $periodCommand1->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $timeSlot = new SaveTimeSlotCommand();
        $timeSlot->startTime = new \DateTimeImmutable('08:00', new \DateTimeZone('Europe/Paris'));
        $timeSlot->endTime = new \DateTimeImmutable('22:00', new \DateTimeZone('Europe/Paris'));
        $periodCommand1->timeSlots = [$timeSlot];

        $periodCommand2 = new SavePeriodCommand();
        $periodCommand2->isPermanent = true;
        $periodCommand2->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand2->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand2->endDate = null;
        $periodCommand2->endTime = null;
        $periodCommand2->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $dailyRange = new SaveDailyRangeCommand();
        $dailyRange->applicableDays = [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value];
        $periodCommand2->dailyRange = $dailyRange;
        $periodCommand2->timeSlots = [];

        $periodCommand3 = new SavePeriodCommand();
        $periodCommand3->isPermanent = true;
        $periodCommand3->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand3->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand3->endDate = null;
        $periodCommand3->endTime = null;
        $periodCommand3->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $dailyRange = new SaveDailyRangeCommand();
        $dailyRange->applicableDays = [ApplicableDayEnum::WEDNESDAY->value, ApplicableDayEnum::SUNDAY->value];
        $periodCommand3->dailyRange = $dailyRange;
        $timeSlot = new SaveTimeSlotCommand();
        $timeSlot->startTime = new \DateTimeImmutable('08:00', new \DateTimeZone('Europe/Paris'));
        $timeSlot->endTime = new \DateTimeImmutable('16:00', new \DateTimeZone('Europe/Paris'));
        $periodCommand3->timeSlots = [$timeSlot];

        yield [
            [
                [
                    'JOUR' => [1, 2, 3, 4, 5, 6, 0],
                    'HEURE_DEB' => '08:00',
                    'HEURE_FIN' => '22:00',
                ],
                [
                    'JOUR' => [1, 2],
                    'HEURE_DEB' => '00:00',
                    'HEURE_FIN' => '23:59',
                ],
                [
                    'JOUR' => [0, 3],
                    'HEURE_DEB' => '08:00',
                    'HEURE_FIN' => '16:00',
                ],
            ],
            [$periodCommand1, $periodCommand2, $periodCommand3],
        ];
    }

    /**
     * @dataProvider provideTransformPeriods
     */
    public function testTransformPeriods(array $data, array $periodCommands): void
    {
        $this->doTestTransform(function (array &$regCirculation, SaveMeasureCommand &$measureCommand) use ($data, $periodCommands) {
            $regCirculation['CIRC_REG']['PERIODE_JH'] = $data;
            $measureCommand->periods = $periodCommands;
        });
    }

    public function testNoSiretFound()
    {
        $this->cityProcessor
            ->expects(self::once())
            ->method('getSiretFromInseeCode')
            ->willReturn(null);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $record = [
            'ARR_REF' => 'arr_1',
            'ARR_NOM' => 'nom_1',
            'ARR_COMMUNE' => [
                'ARR_INSEE' => $this->cityCode,
                'ARR_VILLE' => 'La Courneuve',
                'ARR_CODE_POSTAL' => '93120',
            ],
            'REG_TYPE' => 'CIRCULATION',
            'REG_CIRCULATION' => [],
            'ARR_DUREE' => [
                'ARR_TEMPORALITE' => 'PERMANENT',
                'PERIODE_DEBUT' => [
                    '$date' => '2024-02-06T17:25:00Z',
                ],
            ],
        ];

        $transformer = new BacIdfTransformer($this->commandBus, $this->cityProcessor);

        $result = $transformer->transform($record);

        $this->assertNull($result->command);
        $this->assertEquals(
            [
                [
                    'loc' => ['regulation_identifier' => 'arr_1', 'fieldname' => 'ARR_COMMUNE.ARR_INSEE'],
                    'reason' => 'no_siret_found',
                    'insee_code' => $this->cityCode,
                    'impact' => 'skip_regulation',
                ],
            ],
            $result->errors,
        );
    }
}
