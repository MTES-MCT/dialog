<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\ListItemLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Application\Regulation\View\VehicleCharacteristicsView;
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

        $hasPeriod = $row['startPeriod'] || $row['endPeriod'];
        $hasLocation = $row['city'] && $row['roadName'];
        $hasVehicleCharacteristics = $row['maxWeight']
            || $row['maxHeight']
            || $row['maxLength']
            || $row['maxWidth'];

        return new RegulationOrderRecordSummaryView(
            $row['uuid'],
            $row['status'],
            $row['description'],
            $hasPeriod ? new PeriodView(
                $row['startPeriod'],
                $row['endPeriod'],
            ) : null,
            $hasLocation ? new ListItemLocationView(
                $row['roadName'],
                $row['city'],
            ) : null,
            $hasVehicleCharacteristics ? new VehicleCharacteristicsView(
                $row['maxWeight'],
                $row['maxHeight'],
                $row['maxWidth'],
                $row['maxLength'],
            ) : null,
        );
    }
}
