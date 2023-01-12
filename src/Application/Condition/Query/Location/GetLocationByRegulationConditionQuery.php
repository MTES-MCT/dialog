<?php

declare(strict_types=1);

namespace App\Application\Condition\Query\Location;

use App\Application\QueryInterface;

final class GetLocationByRegulationConditionQuery implements QueryInterface
{
    public function __construct(
        public readonly string $regulationConditionUuid,
    ) {
    }
}
