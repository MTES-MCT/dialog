<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;

final class DeleteRegulationOrderStorageCommand implements CommandInterface
{
    public function __construct(
        public readonly RegulationOrder $regulationOrder,
    ) {
    }
}
