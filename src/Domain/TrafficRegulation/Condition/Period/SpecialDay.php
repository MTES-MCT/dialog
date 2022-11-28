<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Period;

use App\Domain\TrafficRegulation\Condition\Period\Enum\SpecialDayTypeEnum;

class SpecialDay
{
    public function __construct(
        private string $uuid,
        private SpecialDayTypeEnum $type,
        private Period $period,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getType(): SpecialDayTypeEnum
    {
        return $this->type;
    }

    public function getPeriod(): Period
    {
        return $this->period;
    }
}
