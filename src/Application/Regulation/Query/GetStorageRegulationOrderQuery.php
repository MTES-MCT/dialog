<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\Regulation\RegulationOrder;

final readonly class GetStorageRegulationOrderQuery implements QueryInterface
{
    public function __construct(
        public RegulationOrder $regulationOrder,
    ) {
    }
}
