<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderSummaryView;
use App\Application\Regulation\View\VehicleCharacteristicsView;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class GetRegulationOrderSummaryQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderSummaryQuery $query): RegulationOrderSummaryView
    {
        $row = $this->regulationOrderRepository->findOneForSummary(
            $query->uuid,
        );

        if (!$row) {
            throw new RegulationOrderNotFoundException();
        }

        $hasPeriod = $row['startPeriod'] || $row['endPeriod'];
        $hasLocation = $row['postalCode']
            && $row['city']
            && $row['roadName']
            && $row['fromHouseNumber']
            && $row['toHouseNumber'];
        $hasVehicleCharacteristics = $row['maxWeight']
            || $row['maxHeight']
            || $row['maxLength']
            || $row['maxWidth'];

        return new RegulationOrderSummaryView(
            $row['uuid'],
            $row['status'],
            $row['description'],
            $hasPeriod ? new PeriodView(
                $row['startPeriod'],
                $row['endPeriod'],
            ) : null,
            $hasLocation ? new DetailLocationView(
                $row['postalCode'],
                $row['city'],
                $row['roadName'],
                $row['fromHouseNumber'],
                $row['toHouseNumber'],
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
