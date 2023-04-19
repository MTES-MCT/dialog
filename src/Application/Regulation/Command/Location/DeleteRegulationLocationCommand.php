<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrderRecord;

final class DeleteRegulationLocationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly RegulationOrderRecord $regulationOrderRecord,
    ) {
    }
}
