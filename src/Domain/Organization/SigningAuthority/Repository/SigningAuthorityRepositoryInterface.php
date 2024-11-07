<?php

declare(strict_types=1);

namespace App\Domain\Organization\SigningAuthority\Repository;

use App\Domain\Organization\SigningAuthority\SigningAuthority;

interface SigningAuthorityRepositoryInterface
{
    public function findOneByOrganizationUuid(string $organizationUuid): ?SigningAuthority;

    public function add(SigningAuthority $signingAuthority): SigningAuthority;
}
