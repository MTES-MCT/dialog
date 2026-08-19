<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetLatestRegulationsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    /** @return RegulationOrderListItemView[] */
    public function __invoke(GetLatestRegulationsQuery $query): array
    {
        $rows = $this->repository->findLatestRegulations($query->organizationUuids, $query->maxResults);

        $regulationOrderViews = [];

        foreach ($rows as $row) {
            $regulationOrderViews[] = RegulationOrderListItemView::fromRow($row);
        }

        return $regulationOrderViews;
    }
}
