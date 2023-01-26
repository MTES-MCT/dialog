<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final class GetRegulationOrderRecordSummaryQuery implements QueryInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
