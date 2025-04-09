<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;

final readonly class GetRegulationOrderTemplatesQueryHandler
{
    public function __construct(
        private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderTemplatesQuery $query): array
    {
        return $this->regulationOrderTemplateRepository->findByFilters($query->dto);
    }
}
