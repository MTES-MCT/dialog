<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

readonly class VisasAndReasonsView
{
    public function __construct(
        public array $visas = [],
        public array $reasons = [],
    ) {
    }
}
