<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Condition\Query\Period;

use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQueryHandler;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOverallPeriodByRegulationConditionQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $overallPeriod = $this->createMock(OverallPeriod::class);
        $overallPeriodRepository = $this->createMock(OverallPeriodRepositoryInterface::class);
        $overallPeriodRepository
            ->expects(self::once())
            ->method('findOneByRegulationConditionUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($overallPeriod);

        $handler = new GetOverallPeriodByRegulationConditionQueryHandler($overallPeriodRepository);
        $result = $handler(new GetOverallPeriodByRegulationConditionQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($overallPeriod, $result);
    }
}
