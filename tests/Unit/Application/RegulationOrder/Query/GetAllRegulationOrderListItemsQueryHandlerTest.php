<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder;

use App\Application\RegulationOrder\Query\GetAllRegulationOrderListItemsQueryHandler;
use App\Application\RegulationOrder\Query\GetAllRegulationOrderListItemsQuery;
use App\Application\RegulationOrder\View\RegulationOrderListItemView;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetAllRegulationOrderListItemsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $regulationOrderRepository
            ->expects(self::once())
            ->method("findAllDescriptions")
            ->willReturn(["Regulation 1", "Regulation 2"]);

        $handler = new GetAllRegulationOrderListItemsQueryHandler($regulationOrderRepository);

        $regulationOrders = $handler(new GetAllRegulationOrderListItemsQuery());

        $this->assertEquals(
            [
                new RegulationOrderListItemView("Regulation 1"),
                new RegulationOrderListItemView("Regulation 2"),
            ],
            $regulationOrders,
        );
    }
}
