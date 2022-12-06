<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Application\CommandInterface;

final class UpdateRegulationOrderCommand implements CommandInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $description,
        public readonly string $issuingAuthority
    ) {
    }
}
