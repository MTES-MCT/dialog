<?php

declare(strict_types=1);

namespace App\Application;

readonly class RoadLine
{
    public function __construct(
        public string $geometry,
        public string $id,
        public string $roadName,
        public string $cityCode,
    ) {
    }
}
