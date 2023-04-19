<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location;

interface LocationRepositoryInterface
{
    public function add(Location $location): Location;

    public function findOneByUuid(string $uuid): ?Location;
}
