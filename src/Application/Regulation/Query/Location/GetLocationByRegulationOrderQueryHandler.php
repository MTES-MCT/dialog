<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class GetLocationByRegulationOrderQueryHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(GetLocationByRegulationOrderQuery $query): ?Location
    {
        return $this->locationRepository
            ->findOneByRegulationOrderUuid($query->regulationOrderUuid);
    }
}
