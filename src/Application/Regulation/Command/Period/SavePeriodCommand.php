<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;

final class SavePeriodCommand implements CommandInterface
{
    public ?array $applicableDays;
    public ?\DateTimeInterface $startDate;
    public ?\DateTimeInterface $startHour;
    public ?\DateTimeInterface $endDate;
    public ?\DateTimeInterface $endHour;
    public ?string $recurrenceType;

    public ?Measure $measure;

    public function __construct(
        public readonly ?Period $period = null,
    ) {
        $this->applicableDays = $period?->getApplicableDays();
        $this->startDate = $period?->getStartDate();
        $this->startHour = $period?->getStartDate();
        $this->endDate = $period?->getEndDate();
        $this->endHour = $period?->getEndDate();
        $this->recurrenceType = $period?->getRecurrenceType();
    }

    public function sortApplicableDays(): void
    {
        usort($this->applicableDays, function (string $d1, string $d2) {
            return ApplicableDayEnum::getDayIndex($d1) <=> ApplicableDayEnum::getDayIndex($d2);
        });
    }

    public function clear(): void
    {
        if ($this->recurrenceType !== PeriodRecurrenceTypeEnum::SOME_DAYS->value) {
            $this->applicableDays = [];
        }
    }
}
