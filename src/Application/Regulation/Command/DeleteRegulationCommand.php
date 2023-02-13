<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final class DeleteRegulationCommand implements CommandInterface
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $uuid,
    ) {
    }
}
