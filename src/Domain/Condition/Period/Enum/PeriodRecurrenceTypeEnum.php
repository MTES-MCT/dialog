<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period\Enum;

enum PeriodRecurrenceTypeEnum: string
{
    case EVERY_DAYS = 'everyDays';
    case WEEK = 'week';
    case WEEKEND = 'weekend';
    case SOME_DAYS = 'someDays';
    case PART_OF_THE_WEEK = 'partOfTheWeek';
}
