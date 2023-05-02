<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationLocationsInterface;

class CanRegulationOrderRecordBePublished
{
    public function isSatisfiedBy(RegulationLocationsInterface $regulation): bool
    {
        return $regulation->countLocations() >= 1;
    }
}
