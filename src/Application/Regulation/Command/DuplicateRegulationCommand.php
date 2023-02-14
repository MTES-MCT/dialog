<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\User\Organization;

final class DuplicateRegulationCommand implements CommandInterface
{
    public function __construct(
        public readonly Organization $organization,
        public readonly RegulationOrder $originalRegulationOrder,
    ) {
    }
}
