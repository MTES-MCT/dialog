<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;

final class SaveTimeSlotCommand implements CommandInterface
{
    public ?\DateTimeInterface $startTime;
    public ?\DateTimeInterface $endTime;
    public ?Period $period;

    public function __construct(
        public readonly ?TimeSlot $timeSlot = null,
    ) {
        $this->initFromEntity($timeSlot);
    }

    public function initFromEntity(?TimeSlot $timeSlot): self
    {
        $this->startTime = $timeSlot?->getStartTime();
        $this->endTime = $timeSlot?->getEndTime();

        return $this;
    }
}
