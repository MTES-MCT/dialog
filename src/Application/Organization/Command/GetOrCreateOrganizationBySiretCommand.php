<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\CommandInterface;

final class GetOrCreateOrganizationBySiretCommand implements CommandInterface
{
    public function __construct(
        public readonly string $siret,
    ) {
    }
}
