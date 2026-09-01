<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper\Transformers;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Infrastructure\DTO\Event\SaveDailyRangeDTO;
use App\Infrastructure\DTO\Event\SavePeriodDTO;
use App\Infrastructure\DTO\Event\SaveTimeSlotDTO;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

final class PeriodsTransformer implements TransformCallableInterface
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return $this->toCommands(\is_array($value) ? $value : []);
    }

    public function toCommands(?array $periodDtos = []): array
    {
        $commands = [];

        if (!$periodDtos) {
            return $commands;
        }

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
                    $ts->startTime = $this->timeToClientUtc(DateTimeTransformers::fromIso($tsDto->startTime));
                    $ts->endTime = $this->timeToClientUtc(DateTimeTransformers::fromIso($tsDto->endTime));
                    $cmd->timeSlots[] = $ts;
                }
            }

            $commands[] = $cmd;
        }

        return $commands;
    }

    /**
     * Time slots carry a wall-clock time of day only (no meaningful date/offset).
     * The IHM stores them as UTC times of day corresponding to the client timezone
     * wall clock (TimeType view_timezone = client, model_timezone = UTC). We apply
     * the same convention here so a value entered via the API and via the IHM are
     * stored — and later read back — consistently.
     */
    private function timeToClientUtc(?\DateTimeImmutable $time): ?\DateTimeImmutable
    {
        if (!$time) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(
            '!H:i',
            $time->format('H:i'),
            new \DateTimeZone($this->clientTimezone),
        )->setTimezone(new \DateTimeZone('UTC'));
    }
}
