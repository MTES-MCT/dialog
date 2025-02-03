<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Domain\Regulation\Repository\StorageAreaRepositoryInterface;

final class GetStorageAreasByRoadNumbersQueryHandler
{
    public function __construct(
        private StorageAreaRepositoryInterface $storageAreaRepository,
    ) {
    }

    public function __invoke(GetStorageAreasByRoadNumbersQuery $query): array
    {
        return $this->storageAreaRepository->findAllByRoadNumbers($query->roadNumbers);
    }
}
