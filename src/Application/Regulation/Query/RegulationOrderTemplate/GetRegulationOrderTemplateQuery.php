<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\RegulationOrderTemplate;

use App\Application\QueryInterface;

final class GetRegulationOrderTemplateQuery implements QueryInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
