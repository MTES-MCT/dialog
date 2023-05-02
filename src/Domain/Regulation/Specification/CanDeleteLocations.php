<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationLocationsInterface;

final class CanDeleteLocations
{
    public function isSatisfiedBy(RegulationLocationsInterface $regulation): bool
    {
        return $regulation->countLocations() > 1;
    }
}
