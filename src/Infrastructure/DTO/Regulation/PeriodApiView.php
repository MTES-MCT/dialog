<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\TimeSlotView;

final readonly class PeriodApiView
{
    /**
     * @param TimeSlotApiView[] $timeSlots
     */
    public function __construct(
        public string $recurrenceType,
        public ?\DateTimeInterface $startDateTime,
        public ?\DateTimeInterface $endDateTime,
        public ?DailyRangeApiView $dailyRange,
        public array $timeSlots,
    ) {
    }

    public static function fromView(PeriodView $view): self
    {
        $timeSlots = [];

        foreach ($view->timeSlots ?? [] as $timeSlot) {
            \assert($timeSlot instanceof TimeSlotView);
            $timeSlots[] = TimeSlotApiView::fromView($timeSlot);
        }

        return new self(
            recurrenceType: $view->recurrenceType,
            startDateTime: $view->startDateTime,
            endDateTime: $view->endDateTime,
            dailyRange: $view->dailyRange ? DailyRangeApiView::fromView($view->dailyRange) : null,
            timeSlots: $timeSlots,
        );
    }
}
