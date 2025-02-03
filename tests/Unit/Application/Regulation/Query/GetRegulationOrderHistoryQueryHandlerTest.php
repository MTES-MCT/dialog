<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderHistoryQuery;
use App\Application\Regulation\Query\GetRegulationOrderHistoryQueryHandler;
use App\Application\Regulation\View\RegulationOrderHistoryView;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderHistoryQueryHandlerTest extends TestCase
{
    public function testGetRegulationOrderHistory(): void
    {
        $mockDate = new \DateTime('2025-01-31 08:41:57');
        $row = [
            'date' => $mockDate,
            'action' => 'create',
        ];
        $repository = $this->createMock(RegulationOrderHistoryRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('findLastRegulationOrderHistoryByUuid')
            ->with('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a')
            ->willReturn($row);

        $regulationOrderHistoryView = new RegulationOrderHistoryView(
            date: '31-01-2025',
            action: ActionTypeEnum::CREATE->value,
        );

        $handler = new GetRegulationOrderHistoryQueryHandler($repository);
        $result = $handler(new GetRegulationOrderHistoryQuery('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a'));

        $this->assertEquals($regulationOrderHistoryView, $result);
    }
}
