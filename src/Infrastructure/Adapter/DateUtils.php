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

    public function mergeDateAndTime(\DateTimeInterface $date, \DateTimeInterface $time): \DateTimeInterface
    {
        // Need to translate back to the client timezone in which date and time were entered.
        // For example, if a user has entered this via a form from a browser in UTC+1:
        // * $date = 2024-02-05 (-> date = 2024-02-04 23:00:00 UTC)
        // * $time 09:00 (-> time = 2024-02-05 08:00:00 UTC)
        // Then we want to get 2024-02-05 08:00:00 UTC.
        // But if merging in UTC, we would get 2024-02-04 08:00:00 UTC (correct hour, wrong day).
        $clientDate = \DateTimeImmutable::createFromInterface($date)->setTimezone($this->clientTimezone);
        $clientTime = \DateTimeImmutable::createFromInterface($time)->setTimezone($this->clientTimezone);

        $hour = (int) $clientTime->format('H');
        $min = (int) $clientTime->format('i');

        return $clientDate
            ->setTime($hour, $min)
            ->setTimeZone(new \DateTimeZone('UTC'));
    }

    public function isClientFutureDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        return $today < $day;
    }

    public function isClientPastDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        return $day < $today;
    }

    public function formatDateTime(\DateTimeInterface $date, \DateTimeInterface|bool|null $time = null): string
    {
        $dateTime = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone);
        $format = 'd/m/Y';

        if ($time) {
            $time = \DateTimeImmutable::createFromInterface($time === true ? $date : $time)->setTimezone($this->clientTimezone);
            $dateTime = $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
            $format = 'd/m/Y à H\hi';
        }

        return $dateTime->format($format);
    }
}
