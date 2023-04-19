<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationPublicationInterface;

class CanRegulationOrderRecordBePublished
{
    public function isSatisfiedBy(RegulationPublicationInterface $regulation): bool
    {
        return $regulation->countLocations() >= 1;
    }
}
