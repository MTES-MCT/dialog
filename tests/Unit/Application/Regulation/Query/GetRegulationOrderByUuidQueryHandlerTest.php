<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderByUuidQuery;
use App\Application\Regulation\Query\GetRegulationOrderByUuidQueryHandler;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderByUuidQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($regulationOrder);

        $handler = new GetRegulationOrderByUuidQueryHandler($regulationRepository);
        $result = $handler(new GetRegulationOrderByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($regulationOrder, $result);
    }

    public function testNoRegulationOrder(): void
    {
        $this->expectException(RegulationOrderNotFoundException::class);

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrderRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn(null);

        $handler = new GetRegulationOrderByUuidQueryHandler($regulationOrderRepository);
        $handler(new GetRegulationOrderByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
