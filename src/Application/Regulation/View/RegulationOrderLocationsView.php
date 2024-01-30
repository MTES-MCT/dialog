<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\RegulationLocationsInterface;

class RegulationOrderLocationsView implements RegulationLocationsInterface
{
    public function __construct(
        public readonly array $locations,
    ) {
    }

    public function countLocations(): int
    {
        return \count($this->locations);
    }
}
