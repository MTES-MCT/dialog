<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location;

interface LocationRepositoryInterface
{
    public function save(Location $location): Location;

    public function findOneByRegulationOrderUuid(string $uuid): ?Location;
}
