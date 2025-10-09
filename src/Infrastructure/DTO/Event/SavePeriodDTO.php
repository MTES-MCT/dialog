<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Infrastructure\Mapper\Transformers\DateTimeTransformers;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SavePeriodCommand::class)]
final class SavePeriodDTO
{
    #[Map(transform: [DateTimeTransformers::class, 'fromIso'])]
    public ?string $startDate = null; // ISO 8601 date
    #[Map(transform: [DateTimeTransformers::class, 'fromIso'])]
    public ?string $startTime = null; // ISO 8601 time
    #[Map(transform: [DateTimeTransformers::class, 'fromIso'])]
    public ?string $endDate = null;   // ISO 8601 date
    #[Map(transform: [DateTimeTransformers::class, 'fromIso'])]
    public ?string $endTime = null;   // ISO 8601 time
    #[Map(target: 'recurrenceType', transform: [EnumTransformers::class, 'toString'])]
    public ?PeriodRecurrenceTypeEnum $recurrenceType = null;
    public ?bool $isPermanent = false;
    /** @var SaveTimeSlotDTO[]|null */
    public ?array $timeSlots = [];
    public ?SaveDailyRangeDTO $dailyRange = null;
}
