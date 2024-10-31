<?php

declare(strict_types=1);

namespace App\Application\Organization\VisaModel\Query;

use App\Domain\Organization\VisaModel\Repository\VisaModelRepositoryInterface;

final class GetVisaModelsQueryHandler
{
    public function __construct(
        private VisaModelRepositoryInterface $visaModelRepository,
    ) {
    }

    public function __invoke(GetVisaModelsQuery $query): array
    {
        return $this->visaModelRepository->findAll($query->organizationUuid);
    }
}
