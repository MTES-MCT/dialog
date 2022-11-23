<?php

declare(strict_types=1);

namespace App\Tests\Domain\TrafficRegulation;

use App\Domain\TrafficRegulation\RegulationCondition;
use App\Domain\TrafficRegulation\TrafficRegulation;
use PHPUnit\Framework\TestCase;

final class RegulationConditionTest extends TestCase
{
    public function testGetters(): void
    {
        $trafficRegulation = new TrafficRegulation('6598fd41-85cb-42a6-9693-1bc45f4dd392');
        $regulationCondition = new RegulationCondition(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            false,
            $trafficRegulation,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $regulationCondition->getUuid());
        $this->assertSame(false, $regulationCondition->isNegate());
        $this->assertSame($trafficRegulation, $regulationCondition->getTrafficRegulation());
        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationCondition->getTrafficRegulation()->getUuid());
    }
}
