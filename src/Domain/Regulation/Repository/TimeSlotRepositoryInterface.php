<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\Period\TimeSlot;

interface TimeSlotRepositoryInterface
{
    public function delete(TimeSlot $timeSlot): void;
}
