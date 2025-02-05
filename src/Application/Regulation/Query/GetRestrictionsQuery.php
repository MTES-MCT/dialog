<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\Regulation\DTO\RestrictionListFilterDTO;

final readonly class GetRestrictionsQuery implements QueryInterface
{
    public function __construct(
        public RestrictionListFilterDTO $dto,
    ) {
    }
}
