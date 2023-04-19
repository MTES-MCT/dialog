<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\User\Organization;

final class CountRegulationsByOrganizationQuery implements QueryInterface
{
    public function __construct(
        public readonly Organization $organization,
        public readonly bool $isPermanent,
    ) {
    }
}
