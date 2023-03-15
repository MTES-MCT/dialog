<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;

final class GetLocationByRegulationOrderQuery implements QueryInterface
{
    public function __construct(
        public readonly string $regulationOrderUuid,
    ) {
    }
}
