<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetRegulationOrdersToDatexFormatQuery implements QueryInterface
{
    public function __construct(
        public bool $includePermanent = true,
        public bool $includeTemporary = true,
        public bool $includeExpired = false,
    ) {
    }
}
