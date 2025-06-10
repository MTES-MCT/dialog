<?php

declare(strict_types=1);

namespace App\Domain\Organization\Establishment\Repository;

use App\Domain\Organization\Establishment\Establishment;

interface EstablishmentRepositoryInterface
{
    public function add(Establishment $establishment): void;

    public function flush(): void;
}
