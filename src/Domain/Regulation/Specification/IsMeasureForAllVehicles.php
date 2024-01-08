<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Condition\IsMeasureForAllVehiclesInterface;

class IsMeasureForAllVehicles
{
    public function isSatisfiedBy(
        IsMeasureForAllVehiclesInterface $value,
    ): bool {
        return
            empty($value->getRestrictedTypes())
            && !$value->getMaxWidth()
            && !$value->getMaxLength()
            && !$value->getMaxHeight()
        ;
    }
}
