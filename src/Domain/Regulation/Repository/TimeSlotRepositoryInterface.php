<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\Period\TimeSlot;

interface TimeSlotRepositoryInterface
{
    public function add(TimeSlot $timeSlot): TimeSlot;

    public function delete(TimeSlot $timeSlot): void;
}
