<?php

declare(strict_types=1);

namespace App\Tests\Domain\TrafficRegulation\Condition\Period;

use App\Domain\TrafficRegulation\Condition\Period\OverallPeriod;
use PHPUnit\Framework\TestCase;

final class OverallPeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $start = new \DateTimeImmutable('2022-11-24');
        $end = new \DateTimeImmutable('2022-11-26');
        $overallPeriod = new OverallPeriod(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $start,
            $end
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $overallPeriod->getUuid());
        $this->assertSame($start, $overallPeriod->getStartPeriod());
        $this->assertSame($end, $overallPeriod->getEndPeriod());
    }
}
