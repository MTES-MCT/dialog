<?php

declare(strict_types=1);

namespace App\Application\Organization\SigningAuthority\Query;

use App\Application\QueryInterface;

final class GetSigningAuthorityByOrganizationQuery implements QueryInterface
{
    public function __construct(
        public readonly string $organizationUuid,
    ) {
    }
}
