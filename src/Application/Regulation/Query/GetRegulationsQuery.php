<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetRegulationsQuery implements QueryInterface
{
    public function __construct(
        public int $pageSize,
        public int $page,
        public ?array $organizationUuids = null,
    ) {
    }
}
