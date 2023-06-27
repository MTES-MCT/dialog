<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $regulationOrders = $this->repository->findRegulationOrdersForDatexFormat();
        $regulationOrderViews = [];

        foreach ($regulationOrders as $regulationOrder) {
            $vehicleConditions = [];

            foreach ($regulationOrder['restrictedVehicleTypes'] ?: [] as $restrictedVehicleType) {
                $vehicleConditions[] = new DatexVehicleConditionView($restrictedVehicleType);
            }

            foreach ($regulationOrder['exemptedVehicleTypes'] ?: [] as $exemptedVehicleType) {
                $vehicleConditions[] = new DatexVehicleConditionView($exemptedVehicleType, isExempted: true);
            }

            $regulationOrderViews[] = new RegulationOrderDatexListItemView(
                $regulationOrder['uuid'],
                $regulationOrder['organizationName'],
                $regulationOrder['description'],
                $regulationOrder['startDate'],
                $regulationOrder['endDate'],
                new DatexLocationView(
                    address: $regulationOrder['address'],
                    fromHouseNumber: $regulationOrder['fromHouseNumber'],
                    fromLongitude: $regulationOrder['fromLongitude'],
                    fromLatitude: $regulationOrder['fromLatitude'],
                    toHouseNumber: $regulationOrder['toHouseNumber'],
                    toLongitude: $regulationOrder['toLongitude'],
                    toLatitude: $regulationOrder['toLatitude'],
                ),
                vehicleConditions: $vehicleConditions,
            );
        }

        return $regulationOrderViews;
    }
}
