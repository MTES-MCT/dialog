<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationMeasuresInterface;

class CanRegulationOrderRecordBePublished
{
    public function isSatisfiedBy(RegulationMeasuresInterface $measure): bool
    {
        return $measure->countMeasures() >= 1;
    }
}
