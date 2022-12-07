<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\RegulationOrder\Query;

use App\Application\RegulationOrder\Query\GetAllRegulationOrderListItemsQueryHandler;
use App\Application\RegulationOrder\Query\GetAllRegulationOrderListItemsQuery;
use App\Application\RegulationOrder\View\RegulationOrderListItemView;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetAllRegulationOrderListItemsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrder1 = $this->createMock(RegulationOrder::class);
        $regulationOrder1
            ->expects($this->once())
            ->method('getUuid')
            ->willReturn('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf');
        $regulationOrder1
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn('Description 1');
        $regulationOrder1
            ->expects($this->once())
            ->method('getIssuingAuthority')
            ->willReturn('Autorité 1');

        $regulationOrder2 = $this->createMock(RegulationOrder::class);
        $regulationOrder2
            ->expects($this->once())
            ->method('getUuid')
            ->willReturn('247edaa2-58d1-43de-9d33-9753bf6f4d30');
        $regulationOrder2
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn('Description 2');
        $regulationOrder2
            ->expects($this->once())
            ->method('getIssuingAuthority')
            ->willReturn('Autorité 2');

        $regulationOrderRepository
            ->expects(self::once())
            ->method("findRegulationOrders")
            ->willReturn([$regulationOrder1, $regulationOrder2]);

        $handler = new GetAllRegulationOrderListItemsQueryHandler($regulationOrderRepository);
        $regulationOrders = $handler(new GetAllRegulationOrderListItemsQuery());

        $this->assertEquals(
            [
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'Description 1',
                    'Autorité 1'
                ),
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'Description 2',
                    'Autorité 2'
                ),
            ],
            $regulationOrders,
        );
    }
}
