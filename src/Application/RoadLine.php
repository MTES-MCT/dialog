<?php

declare(strict_types=1);

namespace App\Application;

class RoadLine
{
    public function __construct(
        public readonly string $geometry,
        public readonly string $id,
    ) {
    }
}
