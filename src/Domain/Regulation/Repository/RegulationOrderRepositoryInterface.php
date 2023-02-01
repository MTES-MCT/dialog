<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrder;

interface RegulationOrderRepositoryInterface
{
    public function save(RegulationOrder $regulationOrder): RegulationOrder;
}
