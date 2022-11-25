<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Query;

use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class GetAllRegulationOrdersQueryHandler
{
    public function __construct(private RegulationOrderRepositoryInterface $repository)
    {
    }

    public function __invoke(GetAllRegulationOrdersQuery $query)
    {
        return $this->repository->findAll();
    }
}
