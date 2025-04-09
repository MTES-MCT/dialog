<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;

interface RegulationOrderTemplateRepositoryInterface
{
    public function findByFilters(RegulationOrderTemplateDTO $dto): array;
}
