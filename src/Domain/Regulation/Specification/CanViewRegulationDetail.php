<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;

final class CanViewRegulationDetail
{
    public function isSatisfiedBy(?string $userId, string $status): bool
    {
        return $userId || $status === RegulationOrderRecordStatusEnum::PUBLISHED->value;
    }
}
