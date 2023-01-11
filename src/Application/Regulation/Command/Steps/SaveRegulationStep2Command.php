<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationStep2Command implements CommandInterface
{
    public function __construct(
        public readonly RegulationOrderRecord $regulationOrderRecord,
    ) {
    }
}
