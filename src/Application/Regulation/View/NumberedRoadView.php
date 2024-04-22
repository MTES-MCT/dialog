<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class NumberedRoadView implements LocationViewInterface
{
    public function __construct(
        public ?string $roadNumber = null,
        public ?string $administrator = null,
    ) {
    }
}
