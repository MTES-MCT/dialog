<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\RegulationOrderTemplate;

use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;

final class GetRegulationOrderTemplateQueryHandler
{
    public function __construct(
        private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderTemplateQuery $query): ?RegulationOrderTemplate
    {
        return $this->regulationOrderTemplateRepository->findOneByUuid($query->uuid);
    }
}
