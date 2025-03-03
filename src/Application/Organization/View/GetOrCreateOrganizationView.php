<?php

declare(strict_types=1);

namespace App\Application\Organization\View;

use App\Domain\User\Organization;

final readonly class GetOrCreateOrganizationView
{
    public function __construct(
        public Organization $organization,
        public bool $isCreated,
    ) {
    }
}
