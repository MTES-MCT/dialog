<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\Condition;

interface ConditionRepositoryInterface
{
    public function add(Condition $condition): Condition;
}
