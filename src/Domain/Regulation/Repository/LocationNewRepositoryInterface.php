<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\LocationNew;

interface LocationNewRepositoryInterface
{
    public function add(LocationNew $location): LocationNew;
}
