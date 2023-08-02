<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\ArrayUtils;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private ArrayUtils $arrayUtils,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $rows = $this->repository->findRegulationOrdersForDatexFormat();

        $regulationOrders = $this->arrayUtils->groupBy(fn ($row) => $row['uuid'], $rows);

        $regulationOrderViews = [];

        foreach ($regulationOrders as $regulationOrderRows) {
            $trafficRegulations = [];

            foreach ($regulationOrderRows as $row) {
                $vehicleConditions = [];

                foreach ($row['restrictedVehicleTypes'] ?: [] as $restrictedVehicleType) {
                    $vehicleConditions[] = new DatexVehicleConditionView($restrictedVehicleType);
                }

                foreach ($row['exemptedVehicleTypes'] ?: [] as $exemptedVehicleType) {
                    $vehicleConditions[] = new DatexVehicleConditionView($exemptedVehicleType, isExempted: true);
                }

                $location = new DatexLocationView(
                    address: $row['address'],
                    fromHouseNumber: $row['fromHouseNumber'],
                    fromLongitude: $row['fromLongitude'],
                    fromLatitude: $row['fromLatitude'],
                    toHouseNumber: $row['toHouseNumber'],
                    toLongitude: $row['toLongitude'],
                    toLatitude: $row['toLatitude'],
                );

                $trafficRegulations[] = new DatexTrafficRegulationView(
                    $location,
                    $vehicleConditions,
                );
            }

            $regulationOrderViews[] = new RegulationOrderDatexListItemView(
                $regulationOrderRows[0]['uuid'],
                $regulationOrderRows[0]['organizationName'],
                $regulationOrderRows[0]['description'],
                $regulationOrderRows[0]['startDate'],
                $regulationOrderRows[0]['endDate'],
                $trafficRegulations,
            );
        }

        return $regulationOrderViews;
    }
}
