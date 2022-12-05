<?php

declare(strict_types=1);

namespace App\Tests\Domain\Condition\Period;

use App\Domain\Condition\Period\Period;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $start = new \DateTimeImmutable('2022-12-20');
        $end = new \DateTimeImmutable('2022-12-29');
        $period = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            'Vacances de Noël',
            $start,
            $end
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $period->getUuid());
        $this->assertSame('Vacances de Noël', $period->getName());
        $this->assertSame($start, $period->getStartDate());
        $this->assertSame($end, $period->getEndDate());
        $this->assertSame(null, $period->getOverallValidPeriod()); // automatically set by Doctrine
        $this->assertSame(null, $period->getOverallExceptionPeriod()); // automatically set by Doctrine
    }
}
