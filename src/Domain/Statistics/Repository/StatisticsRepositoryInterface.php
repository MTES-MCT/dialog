<?php

declare(strict_types=1);

namespace App\Domain\Statistics\Repository;

interface StatisticsRepositoryInterface
{
    public function addCountStatistics(\DateTimeImmutable $now): void;

    public function addUserActiveStatistics(\DateTimeImmutable $now): void;
}
