<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;

final class SaveDailyRangeDTO
{
    public ?PeriodRecurrenceTypeEnum $recurrenceType = null;
    /** @var string[]|null */
    public ?array $applicableDays = null; // values from ApplicableDayEnum
}
