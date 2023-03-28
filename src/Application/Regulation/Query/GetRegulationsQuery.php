<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\User\Organization;

final class GetRegulationsQuery implements QueryInterface
{
    public function __construct(
        public readonly Organization $organization,
        public readonly int $pageSize,
        public readonly int $page,
        public readonly bool $permanent,
    ) {
    }
}
