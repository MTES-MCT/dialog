<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\News;

interface NewsRepositoryInterface
{
    public function findLatest(): ?News;
}
