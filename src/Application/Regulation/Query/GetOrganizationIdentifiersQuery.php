<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\User\Organization;

final readonly class GetOrganizationIdentifiersQuery implements QueryInterface
{
    public function __construct(
        public Organization $organization,
    ) {
    }
}
