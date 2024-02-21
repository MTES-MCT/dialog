<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationMeasuresInterface;

final class CanDeleteMeasures
{
    public function isSatisfiedBy(RegulationMeasuresInterface $regulation): bool
    {
        return $regulation->countMeasures() > 1;
    }
}
