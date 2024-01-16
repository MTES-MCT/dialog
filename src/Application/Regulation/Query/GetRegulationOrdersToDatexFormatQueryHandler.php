<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $rows = $this->repository->findRegulationOrdersForDatexFormat();

        if (empty($rows)) {
            return [];
        }

        // There is one row per unique combination of (regulationOrder, measure, locationNew).
        // Rows are sorted by regulationOrder uuid.
        // So we iterate over rows, pushing a new regulation order view when the row's regulationOrder uuid changes.
        $regulationOrderViews = [];
        $currentRegulationOrder = $rows[0];
        $trafficRegulations = [];

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
            }

            $vehicleConditions = [];

            foreach ($row['restrictedVehicleTypes'] ?: [] as $restrictedVehicleType) {
                if (VehicleTypeEnum::CRITAIR->value === $restrictedVehicleType) {
                    continue;
                }

                if (VehicleTypeEnum::DIMENSIONS->value === $restrictedVehicleType) {
                    $vehicleConditions[] = new DatexVehicleConditionView(
                        vehicleType: $restrictedVehicleType,
                        maxWidth: $row['maxWidth'],
                        maxLength: $row['maxLength'],
                        maxHeight: $row['maxHeight'],
                    );
                } elseif (VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value === $restrictedVehicleType) {
                    $vehicleConditions[] = new DatexVehicleConditionView(
                        vehicleType: $restrictedVehicleType,
                        maxWeight: $row['heavyweightMaxWeight'],
                    );
                } else {
                    $vehicleConditions[] = new DatexVehicleConditionView(
                        vehicleType: $restrictedVehicleType,
                    );
                }
            }

            foreach ($row['restrictedCritairTypes'] ?: [] as $restrictedCritairTypes) {
                $vehicleConditions[] = new DatexVehicleConditionView($restrictedCritairTypes);
            }

            foreach ($row['exemptedVehicleTypes'] ?: [] as $exemptedVehicleType) {
                $vehicleConditions[] = new DatexVehicleConditionView($exemptedVehicleType, isExempted: true);
            }

            $location = new DatexLocationView(
                roadName: $row['roadName'],
                geometry: $row['geometry'],
            );

            $trafficRegulations[] = new DatexTrafficRegulationView(
                $row['type'],
                $location,
                $vehicleConditions,
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
