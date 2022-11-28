<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Period;

use App\Domain\TrafficRegulation\RegulationCondition;

class OverallPeriod
{
    /** @var Period[] */
    private array $validPeriods = [];

    /** @var Period[] */
    private array $exceptionPeriods = [];

    public function __construct(
        private string $uuid,
        private \DateTimeInterface $startPeriod,
        private \DateTimeInterface $endPeriod,
        private RegulationCondition $regulationCondition,
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

    public function addValidPeriod(Period $period): void
    {
        if (\in_array($period, $this->validPeriods, true)) {
            return;
        }

        $this->validPeriods[] = $period;
    }

    public function getValidPeriods(): array
    {
        return $this->validPeriods;
    }

    public function addExceptionPeriod(Period $period): void
    {
        if (\in_array($period, $this->exceptionPeriods, true)) {
            return;
        }

        $this->exceptionPeriods[] = $period;
    }

    public function getExceptionPeriods(): array
    {
        return $this->exceptionPeriods;
    }

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
    }
}
