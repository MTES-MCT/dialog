<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
use App\Application\Regulation\View\LocationView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startDate1 = new \DateTime('2022-12-07');
        $startDate2 = new \DateTime('2022-12-10');

        $rows = [
            [
                'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                'identifier' => 'F02/2023',
                'status' => 'draft',
                'startDate' => $startDate1,
                'endDate' => null,
                'nbLocations' => 0,
                'location' => null,
                'organizationName' => 'DiaLog',
            ],
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'status' => 'draft',
                'startDate' => $startDate2,
                'endDate' => null,
                'nbLocations' => 2,
                'location' => 'Avenue de Fonneuve#Montauban#82121',
                'organizationName' => 'DiaLog',
            ],
        ];

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationsByOrganizations')
            ->with(['dcab837f-4460-4355-99d5-bf4891c35f8f'], 20, 1, true)
            ->willReturn([
                'count' => 2,
                'items' => $rows,
            ]);

        $handler = new GetRegulationsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationsQuery(
            ['dcab837f-4460-4355-99d5-bf4891c35f8f'],
            20,
            1,
            isPermanent: true,
        ));

        $pagination = new Pagination(
            [
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'F02/2023',
                    'draft',
                    0,
                    'DiaLog',
                    null,
                    $startDate1,
                    null,
                ),
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'F01/2023',
                    'draft',
                    2,
                    'DiaLog',
                    new LocationView(
                        cityCode: '82121',
                        cityLabel: 'Montauban',
                        roadName: 'Avenue de Fonneuve',
                    ),
                    $startDate2,
                    null,
                ),
            ], 2, 1, 20,
        );

        $this->assertEquals($pagination, $regulationOrders);
    }
}
