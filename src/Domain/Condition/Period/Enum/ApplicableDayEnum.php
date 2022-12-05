<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period\Enum;

// We use the DATEXII DayEnum values [docs/spec/datex2/DATEXII_3_Common.xsd]
enum ApplicableDayEnum: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';
}
