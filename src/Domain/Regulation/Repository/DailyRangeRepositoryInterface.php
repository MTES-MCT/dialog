<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\Period\DailyRange;

interface DailyRangeRepositoryInterface
{
    public function add(DailyRange $dailyRange): DailyRange;

    public function delete(DailyRange $dailyRange): void;
}
