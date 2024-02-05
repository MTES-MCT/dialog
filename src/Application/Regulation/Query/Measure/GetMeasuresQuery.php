<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Measure;

use App\Application\QueryInterface;

final class GetMeasuresQuery implements QueryInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
