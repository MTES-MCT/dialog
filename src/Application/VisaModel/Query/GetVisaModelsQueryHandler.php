<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Query;

use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;

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
