<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrder;

interface RegulationOrderRepositoryInterface
{
    public function add(RegulationOrder $regulationOrder): RegulationOrder;

    public function delete(RegulationOrder $regulationOrder): void;
}
