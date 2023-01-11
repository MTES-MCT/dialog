<?php

declare(strict_types=1);

namespace App\Application\Condition\Query\Period;

use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;

final class GetOverallPeriodByRegulationConditionQueryHandler
{
    public function __construct(
        private OverallPeriodRepositoryInterface $overallPeriodRepository,
    ) {
    }

    public function __invoke(GetOverallPeriodByRegulationConditionQuery $query): ?OverallPeriod
    {
        return $this->overallPeriodRepository
            ->findOneByRegulationConditionUuid($query->regulationConditionUuid);
    }
}
