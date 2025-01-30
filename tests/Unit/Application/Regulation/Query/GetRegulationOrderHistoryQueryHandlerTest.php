<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderHistoryQuery;
use App\Application\Regulation\Query\GetRegulationOrderHistoryQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderHistoryQueryHandlerTest extends TestCase
{
    public function testGetRegulationOrderHistory(): void
    {
        $regulationOrderHistory = [];
        $repository = $this->createMock(RegulationOrderHistoryRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('findLastRegulationOrderHistoriesByRegulationOrderUuid')
            ->with('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a')
            ->willReturn($regulationOrderHistory);

        $handler = new GetRegulationOrderHistoryQueryHandler($repository);
        $result = $handler(new GetRegulationOrderHistoryQuery('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a'));

        $this->assertEquals($regulationOrderHistory, $result);
    }
}
