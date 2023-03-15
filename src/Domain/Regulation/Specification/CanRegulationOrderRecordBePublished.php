<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\GetLocationByRegulationOrderQuery;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrderRecord;

class CanRegulationOrderRecordBePublished
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function isSatisfiedBy(RegulationOrderRecord $regulationOrderRecord): bool
    {
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();

        $location = $this->queryBus->handle(
            new GetLocationByRegulationOrderQuery($regulationOrder->getUuid()),
        );

        return $location instanceof Location;
    }
}
