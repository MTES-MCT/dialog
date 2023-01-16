<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Condition\Query\Period;

use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQuery;
use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQueryHandler;
use App\Domain\Condition\Location;
use App\Domain\Condition\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetLocationByRegulationConditionQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $location = $this->createMock(Location::class);
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::once())
            ->method('findOneByRegulationConditionUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($location);

        $handler = new GetLocationByRegulationConditionQueryHandler($locationRepository);
        $result = $handler(new GetLocationByRegulationConditionQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($location, $result);
    }
}
