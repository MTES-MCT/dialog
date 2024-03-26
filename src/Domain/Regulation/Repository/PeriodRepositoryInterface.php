<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\Period\Period;

interface PeriodRepositoryInterface
{
    public function add(Period $period): Period;

    public function delete(Period $period): void;

    public function findAllByMeasureForDatexFormat(string $measureId): array;
}
