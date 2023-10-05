<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DailyRange
{
    private Collection $timeSlots;

    public function __construct(
        private string $uuid,
        private array $applicableDays,
    ) {
        $this->timeSlots = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getApplicableDays(): array
    {
        return $this->applicableDays;
    }

    public function getTimeSlots(): iterable
    {
        return $this->timeSlots;
    }
}
