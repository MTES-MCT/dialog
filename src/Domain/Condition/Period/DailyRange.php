<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

class DailyRange
{
    public function __construct(
        private string $uuid,
        private array $applicableDays,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getApplicableDays(): array
    {
        return $this->applicableDays;
    }
}
