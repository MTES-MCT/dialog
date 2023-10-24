<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;

final class DateUtils implements DateUtilsInterface
{
    private \DateTimeZone $clientTimezone;

    public function __construct(
        string $clientTimezone,
    ) {
        $this->clientTimezone = new \DateTimeZone($clientTimezone);
    }

    public function getNow(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface(
            new \DateTime('now'),
        )->setTimeZone($this->clientTimezone);
    }

    public function getTomorrow(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface(
            new \DateTime('tomorrow'),
        )->setTimeZone($this->clientTimezone);
    }

    public function getMicroTime(): float
    {
        return microtime(true);
    }
}
