<?php

declare(strict_types=1);

namespace App\Application\Visa\Query;

use App\Domain\Visa\Repository\VisaModelRepositoryInterface;

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
