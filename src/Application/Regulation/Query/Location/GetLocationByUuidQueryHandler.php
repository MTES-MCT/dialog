<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class GetLocationByUuidQueryHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(GetLocationByUuidQuery $query): ?Location
    {
        return $this->locationRepository->findOneByUuid($query->uuid);
    }
}
