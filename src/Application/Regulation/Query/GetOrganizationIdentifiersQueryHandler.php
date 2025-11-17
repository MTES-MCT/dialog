<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class GetOrganizationIdentifiersQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetOrganizationIdentifiersQuery $query): array
    {
        return $this->repository->findIdentifiersByOrganization($query->organization);
    }
}
