<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Query;

use App\Application\RegulationOrder\View\RegulationOrderListItemView;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class GetAllRegulationOrderListItemsQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    /** @return \App\Application\RegulationOrder\View\RegulationOrderListItemView[] */
    public function __invoke(GetAllRegulationOrderListItemsQuery $query): array
    {
        $descriptions = $this->repository->findAllDescriptions();

        $build_item = function ($description): RegulationOrderListItemView {
            return new RegulationOrderListItemView($description);
        };

        return array_map($build_item, $descriptions);
    }
}
