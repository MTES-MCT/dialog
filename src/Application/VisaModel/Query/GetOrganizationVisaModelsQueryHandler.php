<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Query;

use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;

final class GetOrganizationVisaModelsQueryHandler
{
    public function __construct(
        private VisaModelRepositoryInterface $visaModelRepository,
    ) {
    }

    public function __invoke(GetOrganizationVisaModelsQuery $query): array
    {
        return $this->visaModelRepository->findOrganizationVisaModels($query->organizationUuid);
    }
}
