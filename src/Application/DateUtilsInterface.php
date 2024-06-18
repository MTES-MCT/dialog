<?php

declare(strict_types=1);

namespace App\Application;

interface DateUtilsInterface
{
    public function getTomorrow(): \DateTimeImmutable;

    public function getNow(): \DateTimeImmutable;

    public function getMicroTime(): float;

    public function mergeDateAndTime(\DateTimeInterface $date, \DateTimeInterface $time): \DateTimeInterface;

    public function isClientFutureDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool;

    public function isClientPastDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool;

    public function formatDateTime(\DateTimeInterface $date, \DateTimeInterface|bool|null $time = null): string;
}
