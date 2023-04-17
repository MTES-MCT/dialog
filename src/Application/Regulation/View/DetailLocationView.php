<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\LocationAddress;

class DetailLocationView
{
    public function __construct(
        public readonly LocationAddress $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
    ) {
    }
}
