<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\PeriodPublicHolidaysProvider;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class PeriodPublicHolidaysProviderTest extends TestCase
{
    private PeriodPublicHolidaysProvider $provider;

    protected function setUp(): void
    {
        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $dateUtils->method('getNow')->willReturn(new \DateTimeImmutable('2026-10-29 10:00:00'));
        $this->provider = new PeriodPublicHolidaysProvider($dateUtils, 'Europe/Paris');
    }

    private function makePeriod(
        bool $isPublicHolidaysExcluded,
        ?string $start,
        ?string $end,
        array $applicableDays = [],
        ?string $departmentCode = '75',
    ): Period {
        $organization = $this->createMock(Organization::class);
        $organization->method('getDepartmentCode')->willReturn($departmentCode);
        $record = $this->createMock(RegulationOrderRecord::class);
        $record->method('getOrganization')->willReturn($organization);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder->method('getRegulationOrderRecord')->willReturn($record);
        $measure = $this->createMock(Measure::class);
        $measure->method('getRegulationOrder')->willReturn($regulationOrder);

        $period = $this->createMock(Period::class);
        $period->method('isPublicHolidaysExcluded')->willReturn($isPublicHolidaysExcluded);
        $period->method('getStartDateTime')->willReturn($start ? new \DateTimeImmutable($start) : null);
        $period->method('getEndDateTime')->willReturn($end ? new \DateTimeImmutable($end) : null);
        $period->method('getMeasure')->willReturn($measure);

        if ($applicableDays) {
            $dailyRange = $this->createMock(DailyRange::class);
            $dailyRange->method('getApplicableDays')->willReturn($applicableDays);
            $period->method('getDailyRange')->willReturn($dailyRange);
        }

        return $period;
    }

    private function format(array $dates): array
    {
        return array_map(fn (\DateTimeImmutable $date) => $date->format(\DateTimeInterface::ATOM), $dates);
    }

    public function testNotExcluded(): void
    {
        $period = $this->makePeriod(false, '2026-01-01T00:00:00+01:00', '2026-12-31T23:59:00+01:00');

        $this->assertSame([], $this->provider->getExcludedPublicHolidays($period));
    }

    public function testHolidaysWithinPeriod(): void
    {
        // Starts during May 1st (overlap), ends before the start of July 14th
        $period = $this->makePeriod(true, '2026-05-01T08:00:00+02:00', '2026-07-13T23:59:00+02:00');

        $this->assertSame([
            '2026-05-01T00:00:00+02:00',
            '2026-05-08T00:00:00+02:00',
            '2026-05-14T00:00:00+02:00',
            '2026-05-25T00:00:00+02:00',
        ], $this->format($this->provider->getExcludedPublicHolidays($period)));
    }

    public function testHolidaysFilteredByApplicableDays(): void
    {
        // May 1st and 8th 2026 are fridays, May 14th is a thursday, May 25th is a monday
        $period = $this->makePeriod(true, '2026-05-01T00:00:00+02:00', '2026-05-31T23:59:00+02:00', ['monday', 'friday']);

        $this->assertSame([
            '2026-05-01T00:00:00+02:00',
            '2026-05-08T00:00:00+02:00',
            '2026-05-25T00:00:00+02:00',
        ], $this->format($this->provider->getExcludedPublicHolidays($period)));
    }

    public function testOpenEndedPeriodIsLimitedToOneYear(): void
    {
        $period = $this->makePeriod(true, '2026-10-01T00:00:00+02:00', null, [], '67');

        $this->assertSame([
            '2026-11-01T00:00:00+01:00',
            '2026-11-11T00:00:00+01:00',
            '2026-12-25T00:00:00+01:00',
            '2026-12-26T00:00:00+01:00',
            '2027-01-01T00:00:00+01:00',
            '2027-03-26T00:00:00+01:00', // Vendredi saint (Alsace-Moselle)
            '2027-03-29T00:00:00+02:00',
            '2027-05-01T00:00:00+02:00',
            '2027-05-06T00:00:00+02:00',
            '2027-05-08T00:00:00+02:00',
            '2027-05-17T00:00:00+02:00',
            '2027-07-14T00:00:00+02:00',
            '2027-08-15T00:00:00+02:00',
        ], $this->format($this->provider->getExcludedPublicHolidays($period)));
    }
}
