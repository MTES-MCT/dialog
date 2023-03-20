<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

class Period
{
    private ?OverallPeriod $overallValidPeriod = null;
    private ?OverallPeriod $overallExceptionPeriod = null;

    public function __construct(
        private string $uuid,
        private ?array $applicableDays = [],
        private ?array $applicableMonths = [],
        private ?array $specialDays = [],
        private ?\DateTimeInterface $dayStartTime = null,
        private ?\DateTimeInterface $dayEndTime = null,
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

    public function getApplicableMonths(): array
    {
        return $this->applicableMonths;
    }

    public function getSpecialDays(): array
    {
        return $this->specialDays;
    }

    public function getDayStartTime(): ?\DateTimeInterface
    {
        return $this->dayStartTime;
    }

    public function getDayEndTime(): ?\DateTimeInterface
    {
        return $this->dayEndTime;
    }

    public function getOverallValidPeriod(): ?OverallPeriod
    {
        return $this->overallValidPeriod;
    }

    public function getOverallExceptionPeriod(): ?OverallPeriod
    {
        return $this->overallExceptionPeriod;
    }
}
