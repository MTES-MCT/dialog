<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Period\Enum;

// We use the DATEXII SpecialDayTypeEnum values [docs/spec/datex2/DATEXII_3_Common.xsd]
enum SpecialDayTypeEnum: string
{
    case PUBLIC_HOLIDAY = 'publicHoliday';
    case DAY_FOLLOWING_PUBLIC_HOLIDAY = 'dayFollowingPublicHoliday';
    case LONG_WEEKEND_DAY = 'longWeekendDay';
    case IN_LIEU_OF_PUBLIC_HOLIDAY = 'inLieuOfPublicHoliday';
    case SCHOOL_DAY = 'schoolDay';
    case SCHOOL_HOLIDAYS = 'schoolHolidays';
    case PUBLIC_EVENT_DAY = 'publicEventDay';
    case OTHER = 'other';
}
