<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Specification;

use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQuery;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\QueryBusInterface;
use App\Domain\Condition\Location;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Specification\CanRegulationOrderBePublished;
use PHPUnit\Framework\TestCase;

final class CanRegulationOrderBePublishedTest extends TestCase
{
    private $queryBus;
    private $location;
    private $overallPeriod;
    private $regulationCondition;
    private $regulationOrder;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->location = $this->createMock(Location::class);
        $this->overallPeriod = $this->createMock(OverallPeriod::class);
        $this->regulationCondition = $this->createMock(RegulationCondition::class);
        $this->regulationCondition
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn('bca28a47-d910-48a7-8d60-90bfbc4d675e');

        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->regulationOrder
            ->expects(self::once())
            ->method('getRegulationCondition')
            ->willReturn($this->regulationCondition);
    }

    public function testRegulationCanBePublished(): void
    {
        $locationQuery = new GetLocationByRegulationConditionQuery('bca28a47-d910-48a7-8d60-90bfbc4d675e');
        $overallPeriodQuery = new GetOverallPeriodByRegulationConditionQuery('bca28a47-d910-48a7-8d60-90bfbc4d675e');

        $this->queryBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$this->equalTo($locationQuery)], [$this->equalTo($overallPeriodQuery)])
            ->willReturnOnConsecutiveCalls($this->location, $this->overallPeriod);

        $specification = new CanRegulationOrderBePublished($this->queryBus);
        $this->assertTrue($specification->isSatisfiedBy($this->regulationOrder));
    }

    public function testNullableLocation(): void
    {
        $locationQuery = new GetLocationByRegulationConditionQuery('bca28a47-d910-48a7-8d60-90bfbc4d675e');
        $overallPeriodQuery = new GetOverallPeriodByRegulationConditionQuery('bca28a47-d910-48a7-8d60-90bfbc4d675e');

        $this->queryBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$this->equalTo($locationQuery)], [$this->equalTo($overallPeriodQuery)])
            ->willReturnOnConsecutiveCalls(null, $this->overallPeriod);

        $specification = new CanRegulationOrderBePublished($this->queryBus);
        $this->assertFalse($specification->isSatisfiedBy($this->regulationOrder));
    }

    public function testNullableOverallPeriod(): void
    {
        $locationQuery = new GetLocationByRegulationConditionQuery('bca28a47-d910-48a7-8d60-90bfbc4d675e');
        $overallPeriodQuery = new GetOverallPeriodByRegulationConditionQuery('bca28a47-d910-48a7-8d60-90bfbc4d675e');

        $this->queryBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$this->equalTo($locationQuery)], [$this->equalTo($overallPeriodQuery)])
            ->willReturnOnConsecutiveCalls($this->location, null);

        $specification = new CanRegulationOrderBePublished($this->queryBus);
        $this->assertFalse($specification->isSatisfiedBy($this->regulationOrder));
    }
}
