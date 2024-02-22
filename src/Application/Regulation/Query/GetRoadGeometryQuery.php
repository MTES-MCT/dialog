<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetRoadGeometryQuery implements QueryInterface
{
    public function __construct(
        public readonly string $roadName,
        public readonly string $cityCode,
    ) {
    }
}
