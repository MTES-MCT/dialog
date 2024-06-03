<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Measure;

use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQueryHandler;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetMeasureByUuidQueryHandlerTest extends TestCase
{
    public function testGetMeasure(): void
    {
        $measure = $this->createMock(Measure::class);
        $measureRepository = $this->createMock(MeasureRepositoryInterface::class);
        $measureRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('db3e218a-ac77-4245-bb80-9b23fc1a2d4e')
            ->willReturn($measure);

        $handler = new GetMeasureByUuidQueryHandler($measureRepository);

        $this->assertSame($measure, $handler(new GetMeasureByUuidQuery('db3e218a-ac77-4245-bb80-9b23fc1a2d4e')));
    }
}
