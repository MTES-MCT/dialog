<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;

final class GetRegulationsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly string $status = RegulationOrderRecordStatusEnum::DRAFT,
    ) {
    }
}
