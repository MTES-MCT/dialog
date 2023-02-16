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
        $start = new \DateTimeImmutable('2022-11-24');
        $end = new \DateTimeImmutable('2022-11-26');
        $overallPeriod = new OverallPeriod(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationCondition,
            $start,
            $end,
        );
        $overallPeriod->addValidPeriod($validPeriod);
        $overallPeriod->addValidPeriod($validPeriod); // Test deduplication
        $overallPeriod->addExceptionPeriod($exceptionPeriod);
        $overallPeriod->addExceptionPeriod($exceptionPeriod); // Test deduplication

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $overallPeriod->getUuid());
        $this->assertSame($start, $overallPeriod->getStartPeriod());
        $this->assertSame($end, $overallPeriod->getEndPeriod());
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

        $start2 = new \DateTimeImmutable('2022-11-24');
        $end2 = new \DateTimeImmutable('2022-11-26');

        $overallPeriod->update($start2, $end2);

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $overallPeriod->getUuid());
        $this->assertSame($start2, $overallPeriod->getStartPeriod());
        $this->assertSame($end2, $overallPeriod->getEndPeriod());
    }
}
