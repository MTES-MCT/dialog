<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

interface StorageAreaRepositoryInterface
{
    public function findAllByAdministratorAndRoadNumber(string $administrator, string $roadNumber): array;
}
