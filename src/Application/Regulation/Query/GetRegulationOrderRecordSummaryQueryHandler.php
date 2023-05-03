<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\MeasureView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrderRecordSummaryQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderRecordSummaryQuery $query): RegulationOrderRecordSummaryView
    {
        $row = $this->regulationOrderRecordRepository->findOneForSummary(
            $query->uuid,
        );

        if (!$row) {
            throw new RegulationOrderRecordNotFoundException();
        }

        $locationViews = [];

        foreach ($row as $regulationOrder) {
            if (empty($regulationOrder['locationUuid'])) {
                continue;
            }

            if (\array_key_exists($regulationOrder['locationUuid'], $locationViews)) {
                array_push(
                    $locationViews[$regulationOrder['locationUuid']]->measures,
                    new MeasureView($regulationOrder['type']),
                );

                continue;
            }

            $locationViews[$regulationOrder['locationUuid']] = new DetailLocationView(
                $regulationOrder['locationUuid'],
                LocationAddress::fromString($regulationOrder['address']),
                $regulationOrder['fromHouseNumber'],
                $regulationOrder['toHouseNumber'],
                $regulationOrder['type'] ? [
                    new MeasureView($regulationOrder['type']),
                ] : null,
            );
        }

        $row = current($row);

        return new RegulationOrderRecordSummaryView(
            uuid: $row['uuid'],
            identifier: $row['identifier'],
            organizationUuid: $row['organizationUuid'],
            organizationName: $row['organizationName'],
            status: $row['status'],
            description: $row['description'],
            locations: $locationViews,
            startDate: $row['startDate'],
            endDate: $row['endDate'],
        );
    }
}
