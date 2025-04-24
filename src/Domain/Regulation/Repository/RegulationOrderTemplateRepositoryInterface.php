<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Domain\Regulation\RegulationOrderTemplate;

interface RegulationOrderTemplateRepositoryInterface
{
    public function add(RegulationOrderTemplate $regulationOrderTemplate): RegulationOrderTemplate;

    public function findByFilters(RegulationOrderTemplateDTO $dto): array;

    public function findOneByUuid(string $uuid): ?RegulationOrderTemplate;
}
