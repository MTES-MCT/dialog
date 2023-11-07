<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\TimeSlot;

final class DeleteTimeSlotCommand implements CommandInterface
{
    public function __construct(
        public readonly TimeSlot $timeSlot,
    ) {
    }
}
