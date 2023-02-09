<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;

final class DeleteRegulationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly array $userOrganizationUuids,
    ) {
    }
}
