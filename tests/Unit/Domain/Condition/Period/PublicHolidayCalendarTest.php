<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\PublicHolidayCalendar;
use PHPUnit\Framework\TestCase;

final class PublicHolidayCalendarTest extends TestCase
{
    public function testNationalHolidays(): void
    {
        $this->assertSame([
            '2026-01-01',
            '2026-04-06', // Lundi de Pâques
            '2026-05-01',
            '2026-05-08',
            '2026-05-14', // Ascension
            '2026-05-25', // Lundi de Pentecôte
            '2026-07-14',
            '2026-08-15',
            '2026-11-01',
            '2026-11-11',
            '2026-12-25',
        ], PublicHolidayCalendar::getDates(2026, '75'));
    }

    public function testEasterBasedHolidaysOnOtherYear(): void
    {
        $dates = PublicHolidayCalendar::getDates(2025);

        $this->assertContains('2025-04-21', $dates);
        $this->assertContains('2025-05-29', $dates);
        $this->assertContains('2025-06-09', $dates);
    }

    public function testAlsaceMoselleHolidays(): void
    {
        foreach (['57', '67', '68'] as $departmentCode) {
            $dates = PublicHolidayCalendar::getDates(2026, $departmentCode);

            $this->assertCount(13, $dates);
            $this->assertContains('2026-04-03', $dates); // Vendredi saint
            $this->assertContains('2026-12-26', $dates);
        }
    }
}
