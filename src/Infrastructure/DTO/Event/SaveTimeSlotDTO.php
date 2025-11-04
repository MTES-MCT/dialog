<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveTimeSlotCommand::class)]
final class SaveTimeSlotDTO
{
    public ?string $startTime = null; // ISO 8601 time
    public ?string $endTime = null; // ISO 8601 time
}
