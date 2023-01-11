<?php

declare(strict_types=1);

namespace App\Application\Condition\Query\Location;

use App\Domain\Condition\Location;
use App\Domain\Condition\Repository\LocationRepositoryInterface;

final class GetLocationByRegulationConditionQueryHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(GetLocationByRegulationConditionQuery $query): ?Location
    {
        return $this->locationRepository
            ->findOneByRegulationConditionUuid($query->regulationConditionUuid);
    }
}
