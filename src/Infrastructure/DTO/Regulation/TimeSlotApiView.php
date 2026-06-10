<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\TimeSlotView;

final readonly class TimeSlotApiView
{
    public function __construct(
        public ?\DateTimeInterface $startTime,
        public ?\DateTimeInterface $endTime,
    ) {
    }

    public static function fromView(TimeSlotView $view): self
    {
        return new self(
            startTime: $view->startTime,
            endTime: $view->endTime,
        );
    }
}
