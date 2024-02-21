<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

interface RegulationMeasuresInterface
{
    public function countMeasures(): int;
}
