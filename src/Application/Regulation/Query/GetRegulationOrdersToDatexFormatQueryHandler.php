<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexTrafficRegulationViewBuilder;
use App\Application\Regulation\View\DatexValidityConditionView;
use App\Application\Regulation\View\DatexValidityConditionViewBuilder;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private PeriodRepositoryInterface $periodRepository,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $rows = $this->repository->findRegulationOrdersForDatexFormat();

        if (empty($rows)) {
            return [];
        }

        // There is one row per unique combination of (regulationOrder, measure, location, period, timeSlot).
        // Rows are sorted by regulationOrder uuid.
        // So we iterate over rows, pushing a new regulation order view when the row's regulationOrder uuid changes.
        $regulationOrderViews = [];
        $currentRegulationOrder = $rows[0];
        $trafficRegulations = [];
        $currentMeasure = $rows[0];
        $currentPeriod = $rows[0];
        $locations = [];
        $dailyRange = null;
        $validityConditions = [];

        $trafficRegulationBuilder = new DatexTrafficRegulationViewBuilder();
        $trafficRegulationBuilder->init($rows[0]);

        $validityConditionViewBuilder = new DatexValidityConditionViewBuilder();
        $validityConditionViewBuilder->init($rows[0]);

        foreach ($rows as $row) {
            if ($row['uuid'] !== $currentRegulationOrder['uuid']) {
                $regulationOrderViews[] = new RegulationOrderDatexListItemView(
                    $currentRegulationOrder['uuid'],
                    $currentRegulationOrder['organizationName'],
                    $currentRegulationOrder['description'],
                    $currentRegulationOrder['startDate'],
                    $currentRegulationOrder['endDate'],
                    $trafficRegulations,
                );
                $currentRegulationOrder = $row;
                $trafficRegulations = [];
                $currentMeasure = $row;
                $locations = [];
                $validityConditions = [];
            }

            if ($row['measureId'] !== $currentMeasure['measureId']) {
                $trafficRegulations[] = $trafficRegulationBuilder->build();
                $trafficRegulationBuilder->init($row);
            } else {
                $trafficRegulationBuilder->handleRow($row);
            }

            if ($row['periodId'] !== $currentPeriod['periodId']) {
                $validityConditions[] = $validityConditionViewBuilder->build();
                $validityConditionViewBuilder->init($row);
            } else {
                $validityConditionViewBuilder->handleRow($row);
            }

            $validityConditions[] = new DatexValidityConditionView(
                $row['startDateTime'],
                $row['endDateTime'],
            );

            $location = new DatexLocationView(
                roadType: $row['roadType'],
                roadName: $row['roadName'],
                roadNumber: $row['roadNumber'],
                geometry: $row['geometry'],
            );

            $trafficRegulations[] = new DatexTrafficRegulationView(
                $row['type'],
                $location,
                $vehicleConditions,
                $validityConditions,
                $row['maxSpeed'],
            );
        }

        // Flush any pending regulation order data into a final view.
        $regulationOrderViews[] = new RegulationOrderDatexListItemView(
            $currentRegulationOrder['uuid'],
            $currentRegulationOrder['organizationName'],
            $currentRegulationOrder['description'],
            $currentRegulationOrder['startDate'],
            $currentRegulationOrder['endDate'],
            $trafficRegulations,
        );

        return $regulationOrderViews;
    }
}
