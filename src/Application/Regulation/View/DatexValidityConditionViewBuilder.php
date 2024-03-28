<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexValidityConditionViewBuilder
{
    private array $currentPeriod;
    private ?\DateTimeInterface $overallStartTime = null;
    private ?\DateTimeInterface $overallEndTime = null;
    private array $validPeriods = [];

    public function init(array $row): void
    {
        $this->currentPeriod = $row;
        $this->overallStartTime = null;
        $this->overallEndTime = null;
        $this->validPeriods = [];

        if ($row['applicableDays'] !== null) {
            $validPeriods[] = ['dayWeekMonthPeriods' => [$row['applicableDays']]];
        }
    }

    public function build(): DatexValidityConditionView
    {
        return new DatexValidityConditionView(
            $this->overallStartTime,
            $this->overallEndTime,
            $this->validPeriods,
        );
    }
}
