<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Query;

use App\Application\QueryInterface;

final class GetOrganizationApiClientsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $organizationUuid,
    ) {
    }
}
