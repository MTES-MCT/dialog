<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\QueryInterface;

final readonly class GetOrganizationUserQuery implements QueryInterface
{
    public function __construct(
        public string $organizationUuid,
        public string $userUuid,
    ) {
    }
}
