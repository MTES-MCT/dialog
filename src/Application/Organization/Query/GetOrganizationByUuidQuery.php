<?php

declare(strict_types=1);

namespace App\Application\Organization\Query;

use App\Application\QueryInterface;

final class GetOrganizationByUuidQuery implements QueryInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
