<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Application\DateUtilsInterface;

final class DateUtilsMock implements DateUtilsInterface
{
    public function getTomorrow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2023-05-10');
    }

    public function getNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2023-06-09');
    }

    public function getMicroTime(): float
    {
        return 1695218778.6387;
    }

    public function mergeDateAndTime(\DateTimeInterface $date1, \DateTimeInterface $date2): \DateTimeInterface
    {
        return new \DateTimeImmutable('2023-06-09 09:00:00');
    }
}
