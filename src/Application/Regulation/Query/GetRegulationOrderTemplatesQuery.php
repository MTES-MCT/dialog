<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;

final readonly class GetRegulationOrderTemplatesQuery implements QueryInterface
{
    public function __construct(
        public RegulationOrderTemplateDTO $dto,
    ) {
    }
}
