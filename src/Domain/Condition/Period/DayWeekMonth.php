<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\ApplicableMonthEnum;

class DayWeekMonth
{
    public function __construct(
        private string $uuid,
        private Period $period,
        private ?ApplicableDayEnum $applicableDay = null,
        private ?ApplicableMonthEnum $applicableMonth = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getApplicableDay(): ?ApplicableDayEnum
    {
        return $this->applicableDay;
    }

    public function getApplicableMonth(): ?ApplicableMonthEnum
    {
        return $this->applicableMonth;
    }

    public function getPeriod(): Period
    {
        return $this->period;
    }
}
