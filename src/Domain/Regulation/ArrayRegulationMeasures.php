<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

readonly class ArrayRegulationMeasures implements RegulationMeasuresInterface
{
    public function __construct(
        private array $measures,
    ) {
    }

    public function countMeasures(): int
    {
        return \count($this->measures);
    }
}
