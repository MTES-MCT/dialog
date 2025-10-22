<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper\Transformers;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Infrastructure\DTO\Event\SaveDailyRangeDTO;
use App\Infrastructure\DTO\Event\SavePeriodDTO;
use App\Infrastructure\DTO\Event\SaveTimeSlotDTO;

final class PeriodsTransformer
{
    public static function toCommands(?array $periodDtos = []): array
    {
        $commands = [];

        foreach ($periodDtos as $dto) {
            if (!$dto instanceof SavePeriodDTO) {
                continue;
            }

            $cmd = new SavePeriodCommand();
            $cmd->recurrenceType = $dto->recurrenceType?->value;
            $cmd->isPermanent = $dto->isPermanent;
            $cmd->startDate = DateTimeTransformers::fromIso($dto->startDate);
            $cmd->startTime = DateTimeTransformers::fromIso($dto->startTime);
            $cmd->endDate = DateTimeTransformers::fromIso($dto->endDate);
            $cmd->endTime = DateTimeTransformers::fromIso($dto->endTime);

            if ($dto->dailyRange instanceof SaveDailyRangeDTO) {
                $dr = new SaveDailyRangeCommand();
                $dr->recurrenceType = $dto->dailyRange->recurrenceType?->value;
                $dr->applicableDays = $dto->dailyRange->applicableDays ?? [];
                $cmd->dailyRange = $dr;
            }

            if ($dto->timeSlots) {
                foreach ($dto->timeSlots as $tsDto) {
                    if (!$tsDto instanceof SaveTimeSlotDTO) {
                        continue;
                    }

                    $ts = new SaveTimeSlotCommand();
                    $ts->startTime = DateTimeTransformers::fromIso($tsDto->startTime);
                    $ts->endTime = DateTimeTransformers::fromIso($tsDto->endTime);
                    $cmd->timeSlots[] = $ts;
                }
            }

            $commands[] = $cmd;
        }

        return $commands;
    }
}
