<?php

declare(strict_types=1);

namespace App\Application\Organization\SigningAuthority\Query;

use App\Domain\Organization\SigningAuthority\Repository\SigningAuthorityRepositoryInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;

final class GetSigningAuthorityByOrganizationQueryHandler
{
    public function __construct(
        private SigningAuthorityRepositoryInterface $signingAuthorityRepository,
    ) {
    }

    public function __invoke(GetSigningAuthorityByOrganizationQuery $query): ?SigningAuthority
    {
        return $this->signingAuthorityRepository->findOneByOrganizationUuid($query->organizationUuid);
    }
}
