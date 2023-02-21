<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\RegulationCondition;
use PHPUnit\Framework\TestCase;

final class OverallPeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);
        $validPeriod = $this->createMock(Period::class);
        $exceptionPeriod = $this->createMock(Period::class);
        $startDate = new \DateTimeImmutable('2022-11-24');
        $startTime = new \DateTimeImmutable('08:00:00');
        $endDate = new \DateTimeImmutable('2022-11-26');
        $overallPeriod = new OverallPeriod(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationCondition,
            startDate: $startDate,
            startTime: $startTime,
            endDate: $endDate,
        );
        $overallPeriod->addValidPeriod($validPeriod);
        $overallPeriod->addValidPeriod($validPeriod); // Test deduplication
        $overallPeriod->addExceptionPeriod($exceptionPeriod);
        $overallPeriod->addExceptionPeriod($exceptionPeriod); // Test deduplication

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $overallPeriod->getUuid());
        $this->assertSame($startDate, $overallPeriod->getStartDate());
        $this->assertSame($startTime, $overallPeriod->getStartTime());
        $this->assertSame($endDate, $overallPeriod->getEndDate());
        $this->assertSame(null, $overallPeriod->getEndTime());
        $this->assertSame($regulationCondition, $overallPeriod->getRegulationCondition());
        $this->assertSame([$validPeriod], $overallPeriod->getValidPeriods());
        $this->assertSame([$exceptionPeriod], $overallPeriod->getExceptionPeriods());
    }

    public function testUpdate(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);
        $start = new \DateTimeImmutable('2022-11-24');
        $end = new \DateTimeImmutable('2022-11-26');
        $overallPeriod = new OverallPeriod(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationCondition,
            $start,
            $end,
        );

        $startDate2 = new \DateTimeImmutable('2022-11-24');
        $startTime2 = new \DateTimeImmutable('08:00:00');
        $endDate2 = new \DateTimeImmutable('2022-11-26');
        $endTime2 = new \DateTimeImmutable('16:00:00');

        $overallPeriod->update($startDate2, $startTime2, $endDate2, $endTime2);

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $overallPeriod->getUuid());
        $this->assertSame($startDate2, $overallPeriod->getStartDate());
        $this->assertSame($startTime2, $overallPeriod->getStartTime());
        $this->assertSame($endDate2, $overallPeriod->getEndDate());
        $this->assertSame($endTime2, $overallPeriod->getEndTime());
    }
}
