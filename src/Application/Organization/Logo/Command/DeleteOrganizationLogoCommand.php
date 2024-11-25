<?php

declare(strict_types=1);

namespace App\Application\Organization\Logo\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final class DeleteOrganizationLogoCommand implements CommandInterface
{
    public function __construct(
        public readonly Organization $organization,
    ) {
    }
}
