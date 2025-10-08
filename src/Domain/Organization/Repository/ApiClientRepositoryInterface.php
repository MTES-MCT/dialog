<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repository;

use App\Domain\Organization\ApiClient;

interface ApiClientRepositoryInterface
{
    public function findOneByClientId(string $clientId): ?ApiClient;
}
