<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQueryHandler;
use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\MeasureView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderRecordSummaryQueryHandlerTest extends TestCase
{
    public function provideGetOne(): array
    {
        return [
            [
                [
                    [
                        'uuid' => '2c85cbb4-cce4-460b-9e68-e8fc9de2c0ea',
                        'address' => 'Avenue de Fonneuve 82000 Montauban',
                        'locationAddress' => new LocationAddress('82000', 'Montauban', 'Avenue de Fonneuve'),
                        'fromHouseNumber' => '695',
                        'toHouseNumber' => '253',
                        'measureType' => MeasureTypeEnum::NO_ENTRY->value,
                    ],
                    [
                        'uuid' => '97ff1036-14cf-4fc4-b14b-0bb4095c9b39',
                        'address' => 'Rue de Paris 82000 Montauban',
                        'locationAddress' => new LocationAddress('82000', 'Montauban', 'Rue de Paris'),
                        'fromHouseNumber' => null,
                        'toHouseNumber' => null,
                        'measureType' => MeasureTypeEnum::NO_ENTRY->value,
                    ],
                ],
            ],
            [
                [
                    [
                        'uuid' => '3edbb5e8-ad7e-4b81-99b9-6b097cf23cb9',
                        'address' => 'Avenue de Fonneuve 82000 Montauban',
                        'locationAddress' => new LocationAddress('82000', 'Montauban', 'Avenue de Fonneuve'),
                        'fromHouseNumber' => null,
                        'toHouseNumber' => null,
                        'measureType' => MeasureTypeEnum::NO_ENTRY->value,
                    ],
                    // Same locations with 2 differents measures
                    [
                        'uuid' => 'fe0a44cb-53b5-4e94-bb7e-dfb8d20be061',
                        'address' => 'Avenue de Paris 82000 Montauban',
                        'locationAddress' => new LocationAddress('82000', 'Montauban', 'Avenue de Paris'),
                        'fromHouseNumber' => '10',
                        'toHouseNumber' => '90',
                        'measureType' => MeasureTypeEnum::NO_ENTRY->value,
                    ],
                    [
                        'uuid' => 'fe0a44cb-53b5-4e94-bb7e-dfb8d20be061',
                        'address' => 'Avenue de Paris 82000 Montauban',
                        'locationAddress' => new LocationAddress('82000', 'Montauban', 'Avenue de Paris'),
                        'fromHouseNumber' => '10',
                        'toHouseNumber' => '90',
                        'measureType' => MeasureTypeEnum::ALTERNATE_ROAD->value,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideGetOne
     */
    public function testGetOne(array $locations): void
    {
        $startDate = new \DateTime('2022-12-07');
        $endDate = new \DateTime('2022-12-17');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $summaryRows = [
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'organizationName' => 'DiaLog',
                'status' => 'draft',
                'category' => 'other',
                'otherCategoryText' => 'Other category 1',
                'description' => 'Description 1',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'locationUuid' => $locations[0]['uuid'],
                'address' => $locations[0]['address'],
                'fromHouseNumber' => $locations[0]['fromHouseNumber'],
                'toHouseNumber' => $locations[0]['toHouseNumber'],
                'measureType' => $locations[0]['measureType'],
            ],
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'organizationName' => 'DiaLog',
                'status' => 'draft',
                'category' => 'other',
                'otherCategoryText' => 'Other category 1',
                'description' => 'Description 1',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'locationUuid' => $locations[1]['uuid'],
                'address' => $locations[1]['address'],
                'fromHouseNumber' => $locations[1]['fromHouseNumber'],
                'toHouseNumber' => $locations[1]['toHouseNumber'],
                'measureType' => $locations[1]['measureType'],
            ],
        ];

        $expectedLocation1Measures = [new MeasureView($locations[1]['measureType'])];

        if (!empty($locations[2])) {
            $summaryRows[] = [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'organizationName' => 'DiaLog',
                'status' => 'draft',
                'category' => 'other',
                'otherCategoryText' => 'Other category 1',
                'description' => 'Description 1',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'locationUuid' => $locations[2]['uuid'],
                'address' => $locations[2]['address'],
                'fromHouseNumber' => $locations[2]['fromHouseNumber'],
                'toHouseNumber' => $locations[2]['toHouseNumber'],
                'measureType' => $locations[2]['measureType'],
            ];

            $expectedLocation1Measures = [
                new MeasureView($locations[1]['measureType']),
                new MeasureView($locations[2]['measureType']),
            ];
        }

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn($summaryRows);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $summary = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                identifier: 'F01/2023',
                organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                organizationName: 'DiaLog',
                status: 'draft',
                category: 'other',
                otherCategoryText: 'Other category 1',
                description: 'Description 1',
                locations: [
                    $locations[0]['uuid'] => new DetailLocationView(
                        $locations[0]['uuid'],
                        $locations[0]['locationAddress'],
                        $locations[0]['fromHouseNumber'],
                        $locations[0]['toHouseNumber'],
                        [new MeasureView($locations[0]['measureType'])],
                    ),
                    $locations[1]['uuid'] => new DetailLocationView(
                        $locations[1]['uuid'],
                        $locations[1]['locationAddress'],
                        $locations[1]['fromHouseNumber'],
                        $locations[1]['toHouseNumber'],
                        $expectedLocation1Measures,
                    ),
                ],
                startDate: $startDate,
                endDate: $endDate,
            ),
            $summary,
        );
    }

    public function testGetOneWithoutExtraData(): void
    {
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord = [
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'organizationName' => 'DiaLog',
                'status' => 'draft',
                'category' => 'event',
                'otherCategoryText' => null,
                'description' => 'Description 1',
                'startDate' => null,
                'endDate' => null,
                'locationUuid' => null,
                'address' => null,
                'fromHouseNumber' => null,
                'toHouseNumber' => null,
            ],
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $summary = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                identifier: 'F01/2023',
                organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                organizationName: 'DiaLog',
                status: 'draft',
                category: 'event',
                otherCategoryText: null,
                description: 'Description 1',
                locations: [],
                startDate: null,
                endDate: null,
            ),
            $summary,
        );
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn(null);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
