<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class MeasureView
{
    public function __construct(
        public readonly string $type,
    ) {
    }
}
