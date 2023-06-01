<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\Query\Factory\LocationMeasuresViewsFactory;
use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrderRecordSummaryQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderRecordSummaryQuery $query): RegulationOrderRecordSummaryView
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
            $locationViews[] = new DetailLocationView(
                uuid: $location->getUuid(),
                address: LocationAddress::fromString($location->getAddress()),
                fromHouseNumber: $location->getFromHouseNumber(),
                toHouseNumber: $location->getToHouseNumber(),
                measures: LocationMeasuresViewsFactory::create($location),
            );
        }

        return new RegulationOrderRecordSummaryView(
            uuid: $regulationOrderRecord->getUuid(),
            identifier: $regulationOrder->getIdentifier(),
            organizationUuid: $regulationOrderRecord->getOrganizationUuid(),
            organizationName: $regulationOrderRecord->getOrganizationName(),
            status: $regulationOrderRecord->getStatus(),
            category: $regulationOrder->getCategory(),
            otherCategoryText: $regulationOrder->getOtherCategoryText(),
            description: $regulationOrder->getDescription(),
            locations: $locationViews,
            startDate: $regulationOrder->getStartDate(),
            endDate: $regulationOrder->getEndDate(),
        );
    }
}
