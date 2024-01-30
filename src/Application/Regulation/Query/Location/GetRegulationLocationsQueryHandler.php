<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\RegulationOrderLocationsView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationLocationsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationLocationsQuery $query): RegulationOrderLocationsView
    {
        $regulationOrderRecord = $this->regulationOrderRecordRepository->findOneForSummary(
            $query->uuid,
        );

        if (!$regulationOrderRecord instanceof RegulationOrderRecord) {
            throw new RegulationOrderRecordNotFoundException();
        }

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $locationViews = [];

        foreach ($regulationOrder->getLocations() as $location) {
            $locationViews[] = DetailLocationView::fromEntity($location);
        }

        return new RegulationOrderLocationsView($locationViews);
    }
}
