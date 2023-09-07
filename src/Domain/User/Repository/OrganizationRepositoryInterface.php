<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Organization;

interface OrganizationRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?Organization;
}
