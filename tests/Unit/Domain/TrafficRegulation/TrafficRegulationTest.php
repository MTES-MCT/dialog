<?php

declare(strict_types=1);

namespace App\Tests\Domain\TrafficRegulation;

use App\Domain\TrafficRegulation\Enum\TrafficRegulationEnum;
use App\Domain\TrafficRegulation\TrafficRegulation;
use PHPUnit\Framework\TestCase;

final class TrafficRegulationTest extends TestCase
{
    public function testGetters(): void
    {
        $trafficRegulation = new TrafficRegulation('6598fd41-85cb-42a6-9693-1bc45f4dd392');

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $trafficRegulation->getUuid());
        $this->assertSame(TrafficRegulationEnum::NO_ENTRY, $trafficRegulation->getType());
    }
}
