<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class ListItemLocationView
{
    public function __construct(
        public readonly string $roadName,
        public readonly string $city,
    ) {
    }
}
