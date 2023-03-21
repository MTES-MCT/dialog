<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\RegulationCondition;

class Period
{
    public function __construct(
        private string $uuid,
        private RegulationCondition $regulationCondition,
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

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
    }
}
