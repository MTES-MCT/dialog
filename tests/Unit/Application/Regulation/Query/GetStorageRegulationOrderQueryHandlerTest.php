<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetStorageRegulationOrderQuery;
use App\Application\Regulation\Query\GetStorageRegulationOrderQueryHandler;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\Regulation\StorageRegulationOrder;
use PHPUnit\Framework\TestCase;

final class GetStorageRegulationOrderQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $storageRegulationOrder = $this->createMock(StorageRegulationOrder::class);
        $storageRegulationOrderRepository = $this->createMock(StorageRegulationOrderRepositoryInterface::class);

        $regulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('aa876485-afeb-4414-9076-b21568959736');

        $storageRegulationOrderRepository
            ->expects(self::once())
            ->method('findOneByRegulationOrderUuid')
            ->with('aa876485-afeb-4414-9076-b21568959736')
            ->willReturn($storageRegulationOrder);

        $handler = new GetStorageRegulationOrderQueryHandler($storageRegulationOrderRepository);
        $command = new GetStorageRegulationOrderQuery($regulationOrder);
        $result = $handler($command);

        $this->assertSame($storageRegulationOrder, $result);
    }
}
