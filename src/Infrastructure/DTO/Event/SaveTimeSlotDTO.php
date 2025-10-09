<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

final class SaveTimeSlotDTO
{
    public ?string $startTime = null; // ISO 8601 time
    public ?string $endTime = null;   // ISO 8601 time
}
