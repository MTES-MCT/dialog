<?php

declare(strict_types=1);

namespace App\Domain\Condition\Repository;

use App\Domain\Condition\Location;

interface LocationRepositoryInterface
{
    public function save(Location $location): Location;

    public function findOneByRegulationConditionUuid(string $uuid): ?Location;
}
