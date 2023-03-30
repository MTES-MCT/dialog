<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
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

        $hasLocation = $row['postalCode']
            && $row['city']
            && $row['roadName'];

        return new RegulationOrderRecordSummaryView(
            $row['uuid'],
            $row['organizationUuid'],
            $row['status'],
            $row['description'],
            $row['startDate'],
            $row['endDate'],
            $hasLocation ? new DetailLocationView(
                $row['postalCode'],
                $row['city'],
                $row['roadName'],
                $row['fromHouseNumber'],
                $row['toHouseNumber'],
            ) : null,
        );
    }
}
