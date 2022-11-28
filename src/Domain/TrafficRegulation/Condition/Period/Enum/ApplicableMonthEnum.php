<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Period\Enum;

// We use the DATEXII MonthOfYearEnum values [docs/spec/datex2/DATEXII_3_Common.xsd]
enum ApplicableMonthEnum: string
{
    case JANUARY = 'january';
    case FEBRUARY = 'february';
    case MARCH = 'march';
    case APRIL = 'april';
    case MAY = 'may';
    case JUNE = 'june';
    case JULY = 'july';
    case AUGUST = 'august';
    case SEPTEMBER = 'september';
    case OCTOBER = 'october';
    case NOVEMBER = 'november';
    case DECEMBER = 'december';
}
