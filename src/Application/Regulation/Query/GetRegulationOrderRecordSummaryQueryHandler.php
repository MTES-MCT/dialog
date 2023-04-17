<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DetailLocationView;
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

        $hasLocation = !empty($row['address']);

        return new RegulationOrderRecordSummaryView(
            $row['uuid'],
            $row['identifier'],
            $row['organizationUuid'],
            $row['organizationName'],
            $row['status'],
            $row['description'],
            $row['startDate'],
            $row['endDate'],
            $hasLocation ? new DetailLocationView(
                LocationAddress::fromString($row['address']),
                $row['fromHouseNumber'],
                $row['toHouseNumber'],
            ) : null,
        );
    }
}
