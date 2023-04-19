<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

interface RegulationPublicationInterface
{
    public function countLocations(): int;
}
