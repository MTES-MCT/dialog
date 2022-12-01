<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder;

use App\Application\RegulationOrder\Query\GetAllRegulationOrdersQuery;
use App\Application\RegulationOrder\Query\GetAllRegulationOrdersQueryHandler;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetAllRegulationOrdersTest extends TestCase
{
    public function testGetAll(): void
    {
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrder1 = $this->createMock(RegulationOrder::class);
        $regulationOrder2 = $this->createMock(RegulationOrder::class);

        $regulationOrderRepository
            ->expects(self::once())
            ->method("findAll")
            ->willReturn([$regulationOrder1, $regulationOrder2]);

        $handler = new GetAllRegulationOrdersQueryHandler($regulationOrderRepository);

        $regulationOrders = $handler(new GetAllRegulationOrdersQuery());

        $this->assertSame([$regulationOrder1, $regulationOrder2], $regulationOrders);
    }
}
