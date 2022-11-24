<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Period;

class OverallPeriod
{
    public function __construct(
        private string $uuid,
        private \DateTimeInterface $startPeriod,
        private \DateTimeInterface $endPeriod,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStartPeriod(): \DateTimeInterface
    {
        return $this->startPeriod;
    }

    public function getEndPeriod(): \DateTimeInterface
    {
        return $this->endPeriod;
    }
}
