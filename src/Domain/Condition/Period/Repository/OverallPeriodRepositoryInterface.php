<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period\Repository;

use App\Domain\Condition\Period\OverallPeriod;

interface OverallPeriodRepositoryInterface
{
    public function save(OverallPeriod $overallPeriod): OverallPeriod;

    public function findOneByRegulationConditionUuid(string $uuid): ?OverallPeriod;
}
