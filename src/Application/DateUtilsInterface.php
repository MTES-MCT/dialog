<?php

declare(strict_types=1);

namespace App\Application;

interface DateUtilsInterface
{
    public function getTomorrow(): \DateTimeImmutable;

    public function getNow(): \DateTimeImmutable;

    public function getMicroTime(): float;

    public function mergeDateAndTime(\DateTimeInterface $date, \DateTimeInterface $time): \DateTimeInterface;

    public function addDays(\DateTimeInterface $dateTime, int $numDays): \DateTimeInterface;
}
