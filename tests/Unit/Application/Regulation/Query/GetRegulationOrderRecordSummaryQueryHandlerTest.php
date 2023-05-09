<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQueryHandler;
use App\Application\Regulation\View\DetailLocationView;
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
    public function testGetOne(array $location): void
    {
        $startDate = new \DateTime('2022-12-07');
        $endDate = new \DateTime('2022-12-17');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord = [
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'organizationName' => 'DiaLog',
                'status' => 'draft',
                'description' => 'Description 1',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'locationUuid' => $location[0]['uuid'],
                'address' => $location[0]['address'],
                'fromHouseNumber' => $location[0]['fromHouseNumber'],
                'toHouseNumber' => $location[0]['toHouseNumber'],
                'measureType' => MeasureTypeEnum::NO_ENTRY->value,
            ],
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'organizationName' => 'DiaLog',
                'status' => 'draft',
                'description' => 'Description 1',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'locationUuid' => $location[1]['uuid'],
                'address' => $location[1]['address'],
                'fromHouseNumber' => $location[1]['fromHouseNumber'],
                'toHouseNumber' => $location[1]['toHouseNumber'],
                'measureType' => MeasureTypeEnum::ALTERNATE_ROAD->value,
            ],
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'F01/2023',
                'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'DiaLog',
                'draft',
                'Description 1',
                [
                    new DetailLocationView(
                        $location[0]['uuid'],
                        $location[0]['locationAddress'],
                        $location[0]['fromHouseNumber'],
                        $location[0]['toHouseNumber'],
                        []
                    ),
                    new DetailLocationView(
                        $location[1]['uuid'],
                        $location[1]['locationAddress'],
                        $location[1]['fromHouseNumber'],
                        $location[1]['toHouseNumber'],
                    ),
                ],
                $startDate,
                $endDate,
            ),
            $regulationOrders,
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
        $regulationOrders = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'F01/2023',
                'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'DiaLog',
                'draft',
                'Description 1',
                [],
                null,
                null,
            ),
            $regulationOrders,
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
