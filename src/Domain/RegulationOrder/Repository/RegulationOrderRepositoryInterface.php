<?php

declare(strict_types=1);

namespace App\Domain\RegulationOrder\Repository;

use App\Domain\RegulationOrder\RegulationOrder;

interface RegulationOrderRepositoryInterface
{
    public function save(RegulationOrder $regulationOrder): RegulationOrder;
}
