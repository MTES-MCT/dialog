<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\User\Command\CreateOrganizationCommand;
use App\Application\User\Query\GetOrganizationBySiretQuery;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\BacIdf\BacIdfCityProcessorInterface;
use App\Infrastructure\BacIdf\BacIdfTransformer;
use App\Infrastructure\BacIdf\BacIdfTransformerResult;
use PHPUnit\Framework\TestCase;

final class BacIdfTransformerTest extends TestCase
{
    private $queryBus;
    private $organization;
    private $cityProcessor;
    private $cityCode = '93027';
    private $siret = '21930027400012';

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->cityProcessor = $this->createMock(BacIdfCityProcessorInterface::class);
    }

    public function testTransform(): void
    {
        $this->cityProcessor
            ->expects(self::once())
            ->method('getSiretFromInseeCode')
            ->willReturn($this->siret);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationBySiretQuery($this->siret))
            ->willReturn($this->organization);

        $record = json_decode(file_get_contents(__DIR__ . '/data/decree1.json'), associative: true);

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = '15.248-circ';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfoCommand->description = 'Circulation passage Pierre Curie';
        $generalInfoCommand->startDate = new \DateTimeImmutable('2015-08-17 00:00');
        $generalInfoCommand->endDate = null;

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->cityCode = $this->cityCode;
        $locationCommand->cityLabel = 'La Courneuve (93120)';
        $locationCommand->roadName = 'Passage Pierre Curie';
        $locationCommand->fromHouseNumber = null;
        $locationCommand->toHouseNumber = null;
        $locationCommand->geometry = json_encode(
            [
                'type' => 'GeometryCollection',
                'geometries' => [
                    [
                        'type' => 'LineString',
                        'coordinates' => [
                            [2.3844267, 48.9207082],
                            [2.3844462, 48.9207571],
                            [2.3847146, 48.9214287],
                            [2.3847305, 48.9214627],
                        ],
                    ],
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

        $importCommand = new ImportBacIdfRegulationCommand($generalInfoCommand, [$measureCommand]);
        $result = new BacIdfTransformerResult($importCommand, [], $this->organization);

        $transformer = new BacIdfTransformer($this->queryBus, $this->cityProcessor);

        $this->assertEquals($result, $transformer->transform($record));
    }

    public function testTransformMinimal(): void
    {
        $this->cityProcessor
            ->expects(self::once())
            ->method('getSiretFromInseeCode')
            ->willReturn($this->siret);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationBySiretQuery($this->siret))
            ->willThrowException(new OrganizationNotFoundException());

        $organizationCommand = new CreateOrganizationCommand();
        $organizationCommand->siret = $this->siret;
        $organizationCommand->name = 'Mairie de La Courneuve';

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
        $generalInfoCommand->description = 'nom_1';
        $generalInfoCommand->startDate = new \DateTimeImmutable('2024-02-06 17:25:00');
        $generalInfoCommand->endDate = null;

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->cityCode = $this->cityCode;
        $locationCommand->cityLabel = 'La Courneuve (93120)';
        $locationCommand->roadName = 'Ruelle du Pressin';
        $locationCommand->fromHouseNumber = null;
        $locationCommand->toHouseNumber = null;
        $locationCommand->geometry = json_encode(
            [
                'type' => 'GeometryCollection',
                'geometries' => [
                    [
                        'type' => 'LineString',
                        'coordinates' => [
                            [2.3820543, 48.9220036],
                            [2.3821628, 48.9221769],
                        ],
                    ],
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
        $result = new BacIdfTransformerResult($importCommand, [], null, $organizationCommand);

        $transformer = new BacIdfTransformer($this->queryBus, $this->cityProcessor);

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

        $this->queryBus
            ->expects(\array_key_exists('ARR_COMMUNE', $record) ? self::once() : self::never())
            ->method('handle');

        $transformer = new BacIdfTransformer($this->queryBus, $this->cityProcessor);

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

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationBySiretQuery($this->siret))
            ->willReturn($this->organization);

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
        $generalInfoCommand->description = 'nom_1';
        $generalInfoCommand->startDate = new \DateTimeImmutable('2024-02-06 17:25:00');
        $generalInfoCommand->endDate = null;

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->cityCode = $this->cityCode;
        $locationCommand->cityLabel = 'La Courneuve (93120)';
        $locationCommand->roadName = 'Ruelle du Pressin';
        $locationCommand->fromHouseNumber = null;
        $locationCommand->toHouseNumber = null;
        $locationCommand->geometry = json_encode(
            [
                'type' => 'GeometryCollection',
                'geometries' => [
                    [
                        'type' => 'LineString',
                        'coordinates' => [
                            [2.3820543, 48.9220036],
                            [2.3821628, 48.9221769],
                        ],
                    ],
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

        $transformer = new BacIdfTransformer($this->queryBus, $this->cityProcessor);

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
        yield [
            [
                [
                    'JOUR' => [1, 2, 3, 4, 5, 6, 0],
                ],
                [
                    'JOUR' => [1, 2, 3, 4, 5, 6, 0],
                    'HEURE_DEB' => '00:00',
                    'HEURE_FIN' => '23:59',
                ],
            ],
            [],
        ];

        $periodCommand1 = new SavePeriodCommand();
        $periodCommand1->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand1->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand1->endDate = new \DateTimeImmutable('2100-01-01T00:00:00+01');
        $periodCommand1->endTime = new \DateTimeImmutable('2100-01-01T00:00:00+01');
        $periodCommand1->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $timeSlot = new SaveTimeSlotCommand();
        $timeSlot->startTime = new \DateTimeImmutable('07:00', new \DateTimeZone('Etc/GMT-1'));
        $timeSlot->endTime = new \DateTimeImmutable('21:00', new \DateTimeZone('Etc/GMT-1'));
        $periodCommand1->timeSlots = [$timeSlot];

        $periodCommand2 = new SavePeriodCommand();
        $periodCommand2->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand2->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand2->endDate = new \DateTimeImmutable('2100-01-01T00:00:00+01');
        $periodCommand2->endTime = new \DateTimeImmutable('2100-01-01T00:00:00+01');
        $periodCommand2->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $dailyRange = new SaveDailyRangeCommand();
        $dailyRange->applicableDays = [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value];
        $periodCommand2->dailyRange = $dailyRange;
        $periodCommand2->timeSlots = [];

        $periodCommand3 = new SavePeriodCommand();
        $periodCommand3->startDate = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand3->startTime = new \DateTimeImmutable('2024-02-06T17:25:00Z');
        $periodCommand3->endDate = new \DateTimeImmutable('2100-01-01T00:00:00+01');
        $periodCommand3->endTime = new \DateTimeImmutable('2100-01-01T00:00:00+01');
        $periodCommand3->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $dailyRange = new SaveDailyRangeCommand();
        $dailyRange->applicableDays = [ApplicableDayEnum::WEDNESDAY->value, ApplicableDayEnum::SUNDAY->value];
        $periodCommand3->dailyRange = $dailyRange;
        $timeSlot = new SaveTimeSlotCommand();
        $timeSlot->startTime = new \DateTimeImmutable('07:00', new \DateTimeZone('Etc/GMT-1'));
        $timeSlot->endTime = new \DateTimeImmutable('15:00', new \DateTimeZone('Etc/GMT-1'));
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

        $transformer = new BacIdfTransformer($this->queryBus, $this->cityProcessor);

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
