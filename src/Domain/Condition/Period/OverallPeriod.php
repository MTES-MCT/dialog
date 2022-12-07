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
        private \DateTimeInterface $startPeriod,
        private ?\DateTimeInterface $endPeriod = null,
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

    public function getEndPeriod(): ?\DateTimeInterface
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
}
