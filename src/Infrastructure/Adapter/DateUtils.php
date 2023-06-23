<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;

final class DateUtils implements DateUtilsInterface
{
    public function getNow(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface(
            new \DateTime('now'),
        );
    }

    public function getTomorrow(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface(
            new \DateTime('tomorrow'),
        );
    }
}
