<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\Period\Period;

interface PeriodRepositoryInterface
{
    public function add(Period $period): Period;
}
