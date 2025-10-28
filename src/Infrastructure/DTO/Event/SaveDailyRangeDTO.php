<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveDailyRangeCommand::class)]
final class SaveDailyRangeDTO
{
    public ?PeriodRecurrenceTypeEnum $recurrenceType = null;
    /** @var string[]|null */
    public ?array $applicableDays = [];
}
