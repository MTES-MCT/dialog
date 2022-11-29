<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Query;

use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class GetRegulationOrderByIdQueryHandler
{
    public function __construct(private RegulationOrderRepositoryInterface $repository)
    {
    }

    public function __invoke(GetRegulationOrderByIdQuery $query): ?RegulationOrder
    {
        return $this->repository->findOneById($query->uuid);
    }
}
