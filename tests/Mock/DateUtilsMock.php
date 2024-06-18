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

    public function isClientFutureDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone(new \DateTimeZone('Etc/GMT-1'))->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone(new \DateTimeZone('Etc/GMT-1'))->setTime(0, 0, 0, 0);

        return $today < $day;
    }

    public function isClientPastDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone(new \DateTimeZone('Etc/GMT-1'))->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone(new \DateTimeZone('Etc/GMT-1'))->setTime(0, 0, 0, 0);

        return $day < $today;
    }

    public function formatDateTime(\DateTimeInterface $date, \DateTimeInterface|bool|null $time = null): string
    {
        $dateTime = \DateTimeImmutable::createFromInterface($date)->setTimeZone(new \DateTimeZone('Etc/GMT-1'));
        $format = 'd/m/Y';

        if ($time) {
            $time = \DateTimeImmutable::createFromInterface($time === true ? $date : $time)->setTimezone(new \DateTimeZone('Etc/GMT-1'));
            $dateTime = $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
            $format = 'd/m/Y à H\hi';
        }

        return $dateTime->format($format);
    }
}
