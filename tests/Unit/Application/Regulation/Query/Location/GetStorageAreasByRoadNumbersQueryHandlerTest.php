<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Query\Location\GetStorageAreasByRoadNumbersQuery;
use App\Application\Regulation\Query\Location\GetStorageAreasByRoadNumbersQueryHandler;
use App\Domain\Regulation\Location\StorageArea;
use App\Domain\Regulation\Repository\StorageAreaRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetStorageAreasByRoadNumbersQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $storageArea1 = $this->createMock(StorageArea::class);
        $storageArea2 = $this->createMock(StorageArea::class);

        $repository = $this->createMock(StorageAreaRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findAllByRoadNumbers')
            ->with(['N176', 'N11', 'N79'])
            ->willReturn(['N79' => [], 'N176' => [$storageArea1, $storageArea2]]);

        $handler = new GetStorageAreasByRoadNumbersQueryHandler($repository);
        $result = $handler(new GetStorageAreasByRoadNumbersQuery(['N176', 'N11', 'N79']));

        $expectedResults = ['N79' => [], 'N176' => [$storageArea1, $storageArea2]];

        $this->assertEquals($expectedResults, $result);
    }
}
