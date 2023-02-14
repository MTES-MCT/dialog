<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQuery;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\QueryBusInterface;
use App\Domain\Condition\Location;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Regulation\RegulationOrder;

class CanRegulationOrderBePublished
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function isSatisfiedBy(RegulationOrder $regulationOrder): bool
    {
        $regulationCondition = $regulationOrder->getRegulationCondition();

        $location = $this->queryBus->handle(
            new GetLocationByRegulationConditionQuery($regulationCondition->getUuid()),
        );

        $overallPeriod = $this->queryBus->handle(
            new GetOverallPeriodByRegulationConditionQuery($regulationCondition->getUuid()),
        );

        return $location instanceof Location && $overallPeriod instanceof OverallPeriod;
    }
}
