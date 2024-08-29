<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\Regulation\DTO\ListRegulationsDTO;

final readonly class GetRegulationsQuery implements QueryInterface
{
    public function __construct(
        public int $pageSize,
        public int $page,
        public ListRegulationsDTO $dto,
    ) {
    }
}
