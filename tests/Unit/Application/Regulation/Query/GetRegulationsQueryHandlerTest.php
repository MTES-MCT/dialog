<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startPeriod1 = new \DateTime('2022-12-07');
        $endPeriod1 = new \DateTime('2022-12-17');
        $startPeriod2 = new \DateTime('2022-12-10');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'startPeriod' => $startPeriod1,
            'endPeriod' => $endPeriod1,
            'status' => 'draft',
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'startPeriod' => $startPeriod2,
            'endPeriod' => null,
            'status' => 'draft',
        ];
        $regulationOrder3 = [
            'uuid' => 'c421193a-5437-431a-9228-db6288d36a16',
            'startPeriod' => null,
            'endPeriod' => null,
            'status' => 'draft',
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("findRegulations")
            ->with(1, 'draft')
            ->willReturn([$regulationOrder1, $regulationOrder2, $regulationOrder3]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("countRegulations")
            ->with('draft')
            ->willReturn(3);

        $handler = new GetRegulationsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationsQuery(1, 'draft'));

        $pagination = new Pagination(
            [
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'draft',
                    new PeriodView($startPeriod1, $endPeriod1),
                ),
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'draft',
                    new PeriodView($startPeriod2),
                ),
                new RegulationOrderListItemView(
                    'c421193a-5437-431a-9228-db6288d36a16',
                    'draft',
                    null
                ),
            ], 3,
        );

        $this->assertEquals($pagination, $regulationOrders);
        $this->assertEquals(1, $pagination->pageCount);
        $this->assertEquals(3, $pagination->totalItems);
    }
}
