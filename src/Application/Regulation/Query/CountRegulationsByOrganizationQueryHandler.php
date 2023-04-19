<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class CountRegulationsByOrganizationQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CountRegulationsByOrganizationQuery $query): int
    {
        return $this->repository->countRegulationsByOrganization(
            $query->organization,
            $query->isPermanent,
        );
    }
}
