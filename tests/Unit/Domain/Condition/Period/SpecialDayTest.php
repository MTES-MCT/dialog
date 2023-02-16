<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\SpecialDayTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\SpecialDay;
use PHPUnit\Framework\TestCase;

final class SpecialDayTest extends TestCase
{
    public function testGetters(): void
    {
        $period = $this->createMock(Period::class);
        $specialDay = new SpecialDay(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            SpecialDayTypeEnum::PUBLIC_EVENT_DAY,
            $period
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $specialDay->getUuid());
        $this->assertSame(SpecialDayTypeEnum::PUBLIC_EVENT_DAY, $specialDay->getType());
        $this->assertSame($period, $specialDay->getPeriod());
    }
}
