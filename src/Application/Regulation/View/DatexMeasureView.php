<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class DatexMeasureView
{
    public function __construct(
        public readonly ?int $maxSpeed = null,
    ) {
    }
}
