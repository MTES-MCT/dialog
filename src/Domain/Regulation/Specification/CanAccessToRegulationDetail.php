<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Application\Regulation\View\RegulationOrderRecordSummaryView;

final class CanAccessToRegulationDetail
{
    public function isSatisfiedBy(RegulationOrderRecordSummaryView $regulationOrderRecord): bool
    {
        return $regulationOrderRecord->status === 'published' && $regulationOrderRecord->location;
    }
}
