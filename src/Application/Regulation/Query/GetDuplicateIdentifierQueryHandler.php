<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class GetDuplicateIdentifierQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetDuplicateIdentifierQuery $query): string
    {
        return $this->repository->getDuplicateIdentifier($query->identifier);
    }
}
