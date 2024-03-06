<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;

final class SavePeriodCommand implements CommandInterface
{
    public ?\DateTimeInterface $startDate;
    public ?\DateTimeInterface $startTime;
    public ?\DateTimeInterface $endDate;
    public ?\DateTimeInterface $endTime;
    public ?string $recurrenceType;
    public ?Measure $measure;
    public ?SaveDailyRangeCommand $dailyRange = null;
    public ?array $timeSlots;
    public bool $isPermanent = false;

    public function __construct(
        public readonly ?Period $period = null,
    ) {
        // Store date and time into separate variables for easier
        // rendering as separate form fields.
        $this->startDate = $period?->getStartDateTime();
        $this->startTime = $period?->getStartDateTime();
        $this->endDate = $period?->getEndDateTime();
        $this->endTime = $period?->getEndDateTime();
        $this->recurrenceType = $period?->getRecurrenceType();

        if ($period?->getDailyRange()) {
            $this->dailyRange = new SaveDailyRangeCommand($period->getDailyRange());
        }

        if ($period?->getTimeSlots()) {
            foreach ($period->getTimeSlots() as $timeSlot) {
                $this->timeSlots[] = new SaveTimeSlotCommand($timeSlot);
            }
        }
    }

    public function clean(): void
    {
        if ($this->recurrenceType !== PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value) {
            $this->dailyRange = null;
        }
    }
}
