<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\LocationAddress;

final class LocationView
{
    public function __construct(
        public readonly LocationAddress $address,
    ) {
    }
}
