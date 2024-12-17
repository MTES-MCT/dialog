<?php

declare(strict_types=1);

namespace App\Domain\Statistics\Repository;

interface StatisticsRepositoryInterface
{
    public function addUserActiveStatistics(\DateTimeImmutable $now): void;
}
