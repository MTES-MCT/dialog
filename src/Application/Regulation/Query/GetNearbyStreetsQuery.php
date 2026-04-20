<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final class GetNearbyStreetsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $geometry,
        public readonly int $radius,
        public readonly int $limit,
    ) {
    }
}
