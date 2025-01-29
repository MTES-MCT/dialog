<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Domain\Regulation\Repository\StorageAreaRepositoryInterface;

final class GetStorageAreasQueryHandler
{
    public function __construct(
        private StorageAreaRepositoryInterface $storageAreaRepository,
    ) {
    }

    public function __invoke(GetStorageAreasQuery $query): array
    {
        return $this->storageAreaRepository->findAll();
    }
}
