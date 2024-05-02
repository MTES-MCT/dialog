<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location\NumberedRoad;

interface NumberedRoadRepositoryInterface
{
    public function add(NumberedRoad $numberedRoad): NumberedRoad;

    public function delete(NumberedRoad $numberedRoad): void;
}
