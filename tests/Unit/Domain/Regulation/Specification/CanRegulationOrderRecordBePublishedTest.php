<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Application\Regulation\Query\Location\GetLocationByRegulationOrderQuery;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use PHPUnit\Framework\TestCase;

final class CanRegulationOrderRecordBePublishedTest extends TestCase
{
    private $queryBus;
    private $location;
    private $regulationOrderRecord;
    private $regulationOrder;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->location = $this->createMock(Location::class);

        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->regulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('b52be4b6-cbc6-4fa0-812f-b01cb7ca08ee');

        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);
    }

    public function testRegulationCanBePublished(): void
    {
        $locationQuery = new GetLocationByRegulationOrderQuery('b52be4b6-cbc6-4fa0-812f-b01cb7ca08ee');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($locationQuery))
            ->willReturn($this->location);

        $specification = new CanRegulationOrderRecordBePublished($this->queryBus);
        $this->assertTrue($specification->isSatisfiedBy($this->regulationOrderRecord));
    }

    public function testNullableLocation(): void
    {
        $locationQuery = new GetLocationByRegulationOrderQuery('b52be4b6-cbc6-4fa0-812f-b01cb7ca08ee');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($locationQuery))
            ->willReturn(null);

        $specification = new CanRegulationOrderRecordBePublished($this->queryBus);
        $this->assertFalse($specification->isSatisfiedBy($this->regulationOrderRecord));
    }
}
