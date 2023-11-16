<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexLocationView
{
    public function __construct(
        public readonly string $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
        public readonly string $gmlPosList,
    ) {
    }
}
