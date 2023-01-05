<?php

declare(strict_types=1);

namespace App\Application\Condition\Query\Period;

use App\Application\QueryInterface;

final class GetOverallPeriodByRegulationConditionQuery implements QueryInterface
{
    public function __construct(
        public readonly string $regulationConditionUuid,
    ) {
    }
}
