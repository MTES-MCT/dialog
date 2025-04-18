<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetRegulationOrderIdentifierQuery implements QueryInterface
{
    public function __construct(
        public string $uuid,
    ) {
    }
}
