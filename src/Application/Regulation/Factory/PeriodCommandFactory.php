<?php

declare(strict_types=1);

namespace App\Application\Regulation\Factory;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Infrastructure\DTO\Event\SavePeriodDTO;

final class PeriodCommandFactory
{
    public function fromDto(SavePeriodDTO $dto): SavePeriodCommand
    {
        $cmd = new SavePeriodCommand();
        $cmd->recurrenceType = $dto->recurrenceType?->value;
        $cmd->isPermanent = $dto->isPermanent;
        if ($dto->startDate) {
            try {
                $cmd->startDate = new \DateTimeImmutable($dto->startDate);
            } catch (\Throwable) {
            }
        }
        if ($dto->startTime) {
            try {
                $cmd->startTime = new \DateTimeImmutable($dto->startTime);
            } catch (\Throwable) {
            }
        }
        if ($dto->endDate) {
            try {
                $cmd->endDate = new \DateTimeImmutable($dto->endDate);
            } catch (\Throwable) {
            }
        }
        if ($dto->endTime) {
            try {
                $cmd->endTime = new \DateTimeImmutable($dto->endTime);
            } catch (\Throwable) {
            }
        }

        if ($dto->dailyRange) {
            $dr = new SaveDailyRangeCommand();
            $dr->recurrenceType = $dto->dailyRange->recurrenceType?->value;
            $dr->applicableDays = $dto->dailyRange->applicableDays ?? [];
            $cmd->dailyRange = $dr;
        }

        if ($dto->timeSlots) {
            $cmd->timeSlots = [];
            foreach ($dto->timeSlots as $slotDto) {
                $ts = new SaveTimeSlotCommand();
                if ($slotDto->startTime) {
                    try {
                        $ts->startTime = new \DateTimeImmutable($slotDto->startTime);
                    } catch (\Throwable) {
                    }
                }
                if ($slotDto->endTime) {
                    try {
                        $ts->endTime = new \DateTimeImmutable($slotDto->endTime);
                    } catch (\Throwable) {
                    }
                }
                $cmd->timeSlots[] = $ts;
            }
        }

        return $cmd;
    }
}
