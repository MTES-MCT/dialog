<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetRegulationsQuery implements QueryInterface
{
    public function __construct(
        public int $pageSize,
        public int $page,
        public ?string $identifier = null,
        public ?array $organizationUuids = null,
        public ?string $regulationOrderType = null,
        public ?string $status = null,
    ) {
    }
}
