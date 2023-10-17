<?php

declare(strict_types=1);

namespace App\Application;

interface DateUtilsInterface
{
    public function getTomorrow(): \DateTimeImmutable;

    public function getNow(): \DateTimeImmutable;

    public function getMicroTime(): float;

    public function mergeDateAndTimeOfTwoDates(\DateTimeInterface $date1, \DateTimeInterface $date2): \DateTimeInterface;
}
