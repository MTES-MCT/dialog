<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\SpecialDayTypeEnum;

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
