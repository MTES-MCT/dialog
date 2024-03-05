<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Domain\Geography\Coordinates;
use App\Infrastructure\EudonetParis\EudonetParisClient;
use App\Infrastructure\EudonetParis\EudonetParisExtractor;
use PHPUnit\Framework\TestCase;

final class EudonetParisExtractorTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(EudonetParisClient::class);
    }

    public function testExtract(): void
    {
        $extractor = new EudonetParisExtractor($this->client);
        $now = new \DateTimeImmutable('2023-08-30 16:00:00 Europe/Paris');

        $regulationOrderRows = [
            ['fileId' => 'arrete1', 'fields' => ['...']],
            ['fileId' => 'arrete2', 'fields' => ['...']],
        ];
        $regulationOrder1MeasureRows = [['fileId' => 'mesure1_1', 'fields' => ['...']]];
        $regulationOrder1Measure1LocationRows = [
            [
                'fileId' => 'localisation1_1_1',
                'fields' => [
                    EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE => 'Rue du Test',
                    EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT => '7',
                    EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN => '15',
                ],
                'fromCoords' => null,
                'toCoords' => null,
            ],
        ];
        $regulationOrder1Measure1Location1StartAddressRow = [
            'fileId' => 'address1_start',
            'fields' => [
                3414 => '2,3',
                3411 => '42,5',
            ],
        ];
        $regulationOrder1Measure1Location1EndAddressRow = [
            'fileId' => 'address1_end',
            'fields' => [
                3414 => '2,5',
                3411 => '42,7',
            ],
        ];
        $regulationOrder2MeasureRows = [
            ['fileId' => 'mesure2_1', 'fields' => ['...']],
            ['fileId' => 'mesure2_2', 'fields' => ['...']],
        ];
        $regulationOrder2Measure1LocationRows = [['fileId' => 'localisation2_1_1', 'fields' => ['...'], 'fromCoords' => null, 'toCoords' => null]];
        $regulationOrder2Measure2LocationRows = [];

        $matcher = self::exactly(8);
        $this->client
            ->expects($matcher)
            ->method('search')
            ->willReturnCallback(fn ($tabId, $listCols, $whereCustom) => match ($matcher->getInvocationCount()) {
                1 => $this->assertSame([
                    1100,
                    [1101, 1102, 1108, 1109, 1110],
                    [
                        'WhereCustoms' => [
                            [
                                'Criteria' => [
                                    'Field' => 1108,
                                    'Operator' => 0,
                                    'Value' => 8,
                                ],
                            ],
                            [
                                'Criteria' => [
                                    'Field' => 1110,
                                    'Operator' => 3,
                                    'Value' => '2023/08/30 16:00:00',
                                ],
                                'InterOperator' => 1,
                            ],
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: $regulationOrderRows,

                2 => $this->assertSame([
                    1200,
                    [1201, 1202],
                    [
                        'WhereCustoms' => [
                            [
                                'Criteria' => [
                                    'Field' => 1100,
                                    'Operator' => 0,
                                    'Value' => 'arrete1',
                                ],
                            ],
                            [
                                'Criteria' => [
                                    'Field' => 1202,
                                    'Operator' => 0,
                                    'Value' => '103',
                                ],
                                'InterOperator' => 1,
                            ],
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: $regulationOrder1MeasureRows,

                3 => $this->assertSame([
                    2700,
                    [2701, 2705, 2708, 2710, 2730, 2740, 2720, 2737],
                    [
                        'Criteria' => [
                            'Field' => 1200,
                            'Operator' => 0,
                            'Value' => 'mesure1_1',
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: $regulationOrder1Measure1LocationRows,

                4 => $this->assertSame([
                    3400,
                    [3414, 3411],
                    [
                        'Criteria' => [
                            'Field' => 3401,
                            'Operator' => 0,
                            'Value' => '7 Rue du Test',
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: [$regulationOrder1Measure1Location1StartAddressRow],

                5 => $this->assertSame([
                    3400,
                    [3414, 3411],
                    [
                        'Criteria' => [
                            'Field' => 3401,
                            'Operator' => 0,
                            'Value' => '15 Rue du Test',
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: [$regulationOrder1Measure1Location1EndAddressRow],

                6 => $this->assertSame([
                    1200,
                    [1201, 1202],
                    [
                        'WhereCustoms' => [
                            [
                                'Criteria' => [
                                    'Field' => 1100,
                                    'Operator' => 0,
                                    'Value' => 'arrete2',
                                ],
                            ],
                            [
                                'Criteria' => [
                                    'Field' => 1202,
                                    'Operator' => 0,
                                    'Value' => '103',
                                ],
                                'InterOperator' => 1,
                            ],
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: $regulationOrder2MeasureRows,

                7 => $this->assertSame([
                    2700,
                    [2701, 2705, 2708, 2710, 2730, 2740, 2720, 2737],
                    [
                        'Criteria' => [
                            'Field' => 1200,
                            'Operator' => 0,
                            'Value' => 'mesure2_1',
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: $regulationOrder2Measure1LocationRows,

                8 => $this->assertSame([
                    2700,
                    [2701, 2705, 2708, 2710, 2730, 2740, 2720, 2737],
                    [
                        'Criteria' => [
                            'Field' => 1200,
                            'Operator' => 0,
                            'Value' => 'mesure2_2',
                        ],
                    ],
                ], [$tabId, $listCols, $whereCustom]) ?: $regulationOrder2Measure2LocationRows,
            });

        $records = iterator_to_array($extractor->iterExtract($now, []));

        $this->assertEquals(
            [
                [
                    'fileId' => 'arrete1',
                    'fields' => ['...'],
                    'measures' => [
                        [
                            'fileId' => 'mesure1_1',
                            'fields' => ['...'],
                            'locations' => [
                                [
                                    'fileId' => 'localisation1_1_1',
                                    'fields' => [
                                        2710 => 'Rue du Test',
                                        2720 => '7',
                                        2737 => '15',
                                    ],
                                    'fromCoords' => Coordinates::fromLonLat(2.3, 42.5),
                                    'toCoords' => Coordinates::fromLonLat(2.5, 42.7),
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fileId' => 'arrete2',
                    'fields' => ['...'],
                    'measures' => [
                        [
                            'fileId' => 'mesure2_1',
                            'fields' => ['...'],
                            'locations' => [
                                [
                                    'fileId' => 'localisation2_1_1',
                                    'fields' => ['...'],
                                    'fromCoords' => null,
                                    'toCoords' => null,
                                ],
                            ],
                        ],
                        [
                            'fileId' => 'mesure2_2',
                            'fields' => ['...'],
                            'locations' => [],
                        ],
                    ],
                ],
            ],
            $records,
        );
    }

    public function testExtractWithIgnoreIDs(): void
    {
        $extractor = new EudonetParisExtractor($this->client);
        $now = new \DateTimeImmutable('2023-08-30 16:00:00 Europe/Paris');

        $matcher = self::exactly(1);
        $this->client
            ->expects($matcher)
            ->method('search')
            ->with(
                1100,
                [1101, 1102, 1108, 1109, 1110],
                [
                    'WhereCustoms' => [
                        [
                            'Criteria' => [
                                'Field' => 1108,
                                'Operator' => 0,
                                'Value' => 8,
                            ],
                        ],
                        [
                            'Criteria' => [
                                'Field' => 1110,
                                'Operator' => 3,
                                'Value' => '2023/08/30 16:00:00',
                            ],
                            'InterOperator' => 1,
                        ],
                        [
                            'Criteria' => [
                                'Field' => 1101,
                                'Operator' => 15,
                                'Value' => '064ef5af-1ec0-77d7-8000-9c9d608d74b5;064ef5b4-b491-7bf9-8000-34710e804dd9',
                            ],
                            'InterOperator' => 1,
                        ],
                    ],
                ],
            );

        iterator_to_array($extractor->iterExtract($now, ['064ef5af-1ec0-77d7-8000-9c9d608d74b5', '064ef5b4-b491-7bf9-8000-34710e804dd9']));
    }
}
