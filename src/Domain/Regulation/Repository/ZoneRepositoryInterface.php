<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location\Zone;

interface ZoneRepositoryInterface
{
    public function add(Zone $zone): Zone;

    public function delete(Zone $zone): void;
}
