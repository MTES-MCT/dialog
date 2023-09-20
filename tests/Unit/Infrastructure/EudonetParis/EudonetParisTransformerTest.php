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
use App\Domain\Geography\GeometryFormatter;
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

    private function provideTransform(): array
    {
        return [
            [
                'arrondissement' => '18ème arrondissement',
                'address' => 'Rue Eugène Berthoud, 75018 Paris',
            ],
            [
                'arrondissement' => '18eme arrondissement',
                'address' => 'Rue Eugène Berthoud, 75018 Paris',
            ],
            [
                'arrondissement' => '18e arrondissement',
                'address' => 'Rue Eugène Berthoud, 75018 Paris',
            ],
            [
                'arrondissement' => '1er arrondissement',
                'address' => 'Rue Eugène Berthoud, 75001 Paris',
            ],
        ];
    }

    /**
     * @dataProvider provideTransform
     */
    public function testTransform(string $arrondissement, string $address): void
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
                        EudonetParisExtractor::MESURE_NOM => 'circulation interdite',
                    ],
                    'locations' => [
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => $arrondissement,
                                EudonetParisExtractor::LOCALISATION_PORTE_SUR => 'La totalité de la voie',
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
        $locationItem->address = $address;
        $locationItem->fromHouseNumber = null;
        $locationItem->fromPoint = null;
        $locationItem->toHouseNumber = null;
        $locationItem->toPoint = null;
        $locationItem->measures = [$measureCommand];

        $importCommand = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$locationItem]);
        $result = new EudonetParisTransformerResult($importCommand, []);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

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
                        EudonetParisExtractor::MESURE_NOM => 'circulation interdite',
                    ],
                    'locations' => [
                        [
                            'fields' => [
                                EudonetParisExtractor::LOCALISATION_ID => 'localisation1',
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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

        $locationItem1 = new EudonetParisLocationItem();
        $locationItem1->address = 'Rue Eugène Berthoud, 75018 Paris';
        $locationItem1->fromHouseNumber = null;
        $locationItem1->fromPoint = 'POINT(48.906236 2.345310)';
        $locationItem1->toHouseNumber = '26';
        $locationItem1->toPoint = null;
        $locationItem1->measures = [$measureCommand];

        $locationItem2 = new EudonetParisLocationItem();
        $locationItem2->address = 'Rue Eugène Berthoud, 75018 Paris';
        $locationItem2->fromHouseNumber = '15';
        $locationItem2->fromPoint = null;
        $locationItem2->toHouseNumber = null;
        $locationItem2->toPoint = 'POINT(48.904560 2.349440)';
        $locationItem2->measures = [$measureCommand];

        $matcher = self::exactly(2);
        $this->geocoder
            ->expects($matcher)
            ->method('computeJunctionCoordinates')
            ->willReturnCallback(fn ($address, $roadName) => match ($matcher->getInvocationCount()) {
                1 => $this->assertEquals(['Rue Eugène Berthoud, 75018 Paris', 'Rue Jean Perrin'], [$address, $roadName]) ?: Coordinates::fromLonLat(48.9062362, 2.3453101),
                2 => $this->assertEquals(['Rue Eugène Berthoud, 75018 Paris', 'Rue Adrien Lesesne'], [$address, $roadName]) ?: Coordinates::fromLonLat(48.9045598, 2.34944),
            });

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

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

        $result = new EudonetParisTransformerResult(null, ['at regulation_order 20230514-1: skip: no measures found']);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

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
            'at regulation_order 20230514-1: skip measure: at measure mesure1: unsupported measure type: interdiction de stationner',
            'at regulation_order 20230514-1: skip: no locations were gathered',
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }

    protected function provideSkipBadArrondissement(): array
    {
        return [
            [
                'arrondissement' => '',
                'skipMessage' => 'at regulation_order 20230514-1: skip location: at location localisation1: ARRONDISSEMENT "" did not have expected format "/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i"',
            ],
            [
                'arrondissement' => 'whatever',
                'skipMessage' => 'at regulation_order 20230514-1: skip location: at location localisation1: ARRONDISSEMENT "whatever" did not have expected format "/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i"',
            ],
            [
                'arrondissement' => '18ème arr',
                'skipMessage' => 'at regulation_order 20230514-1: skip location: at location localisation1: ARRONDISSEMENT "18ème arr" did not have expected format "/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i"',
            ],
            [
                'arrondissement' => '18 arrondissement',
                'skipMessage' => 'at regulation_order 20230514-1: skip location: at location localisation1: ARRONDISSEMENT "18 arrondissement" did not have expected format "/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i"',
            ],
        ];
    }

    /**
     * @dataProvider provideSkipBadArrondissement
     */
    public function testSkipBadArrondissement(string $arrondissement, string $skipMessage): void
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => $arrondissement,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = new EudonetParisTransformerResult(null, [
            $skipMessage,
            'at regulation_order 20230514-1: skip: no locations were gathered',
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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
            'at regulation_order 20230514-1: skip location: at location localisation1: could not extract supported location info: {"fields":{"2701":"localisation1","2708":"18\u00e8me arrondissement","2705":"Autre chose","2710":"...","2730":null,"2740":null,"2720":null,"2737":null}}',
            'at regulation_order 20230514-1: skip: no locations were gathered',
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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
                                EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT => '18ème arrondissement',
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
            'at regulation_order 20230514-1: skip location: at location localisation1: could not extract supported location info: {"fields":{"2701":"localisation1","2708":"18\u00e8me arrondissement","2705":"Une section","2710":"...","2730":"Start road","2740":null,"2720":null,"2737":null}}',
            'at regulation_order 20230514-1: skip location: at location localisation1: could not extract supported location info: {"fields":{"2701":"localisation1","2708":"18\u00e8me arrondissement","2705":"Une section","2710":"...","2730":null,"2740":"End road","2720":null,"2737":null}}',
            'at regulation_order 20230514-1: skip location: at location localisation1: could not extract supported location info: {"fields":{"2701":"localisation1","2708":"18\u00e8me arrondissement","2705":"Une section","2710":"...","2730":null,"2740":null,"2720":"Start house number","2737":null}}',
            'at regulation_order 20230514-1: skip location: at location localisation1: could not extract supported location info: {"fields":{"2701":"localisation1","2708":"18\u00e8me arrondissement","2705":"Une section","2710":"...","2730":null,"2740":null,"2720":null,"2737":"End house number"}}',
            'at regulation_order 20230514-1: skip: no locations were gathered',
        ]);

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $transformer = new EudonetParisTransformer($this->geocoder, new GeometryFormatter());

        $this->assertEquals($result, $transformer->transform($record, $organization));
    }
}
