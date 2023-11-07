<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period\Enum;

enum PeriodRecurrenceTypeEnum: string
{
    case EVERY_DAY = 'everyDay';
    case CERTAIN_DAYS = 'certainDays';

    // Not activated options
    // case WEEK = 'week';
    // case WEEKEND = 'weekend';
    // case PART_OF_THE_WEEK = 'partOfTheWeek';
}
