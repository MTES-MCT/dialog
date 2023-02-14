<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class GetRegulationOrderByUuidQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderByUuidQuery $query): RegulationOrder
    {
        $regulationOrder = $this->regulationOrderRepository->findOneByUuid(
            $query->uuid,
        );

        if (!$regulationOrder instanceof RegulationOrder) {
            throw new RegulationOrderNotFoundException();
        }

        return $regulationOrder;
    }
}
