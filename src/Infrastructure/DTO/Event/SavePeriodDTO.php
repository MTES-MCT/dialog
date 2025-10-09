<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;

final class SavePeriodDTO
{
    public ?string $startDate = null; // ISO 8601 date
    public ?string $startTime = null; // ISO 8601 time
    public ?string $endDate = null;   // ISO 8601 date
    public ?string $endTime = null;   // ISO 8601 time
    public ?PeriodRecurrenceTypeEnum $recurrenceType = null;
    public ?bool $isPermanent = false;
    /** @var SaveTimeSlotDTO[]|null */
    public ?array $timeSlots = null;
    public ?SaveDailyRangeDTO $dailyRange = null;
}
