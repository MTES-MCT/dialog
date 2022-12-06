<?php

declare(strict_types=1);

namespace App\Domain\Condition\Repository;

use App\Domain\Condition\RegulationCondition;

interface RegulationConditionRepositoryInterface
{
    public function save(RegulationCondition $regulationCondition): RegulationCondition;
}
