<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\RegulationCondition;

class OverallPeriod
{
    /** @var Period[] */
    private iterable $validPeriods = [];

    /** @var Period[] */
    private iterable $exceptionPeriods = [];

    public function __construct(
        private string $uuid,
        private RegulationCondition $regulationCondition,
        private \DateTimeInterface $startDate,
        private ?\DateTimeInterface $startTime = null,
        private ?\DateTimeInterface $endDate = null,
        private ?\DateTimeInterface $endTime = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function addValidPeriod(Period $period): void
    {
        if (\in_array($period, $this->validPeriods, true)) {
            return;
        }

        $this->validPeriods[] = $period;
    }

    public function getValidPeriods(): iterable
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

    public function getExceptionPeriods(): iterable
    {
        return $this->exceptionPeriods;
    }

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
    }

    public function update(
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $startTime,
        ?\DateTimeInterface $endDate,
        ?\DateTimeInterface $endTime,
    ): void {
        $this->startDate = $startDate;
        $this->startTime = $startTime;
        $this->endDate = $endDate;
        $this->endTime = $endTime;
    }
}
