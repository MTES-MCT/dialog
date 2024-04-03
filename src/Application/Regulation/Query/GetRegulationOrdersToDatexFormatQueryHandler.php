<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForDatexFormat();

        $regulationOrderViews = [];

        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $regulationOrder = $regulationOrderRecord->getRegulationOrder();

            $trafficRegulations = [];

            /** @var Measure $measure */
            foreach ($regulationOrder->getMeasures() as $measure) {
                $measureType = $measure->getType();
                $maxSpeed = $measure->getMaxSpeed();
                $vehicleSet = $measure->getVehicleSet();

                $vehicleConditions = [];

                foreach ($vehicleSet->getRestrictedTypes() as $restrictedVehicleType) {
                    if (VehicleTypeEnum::CRITAIR->value === $restrictedVehicleType) {
                        foreach ($vehicleSet->getCritairTypes() as $restrictedCritairTypes) {
                            $vehicleConditions[] = new DatexVehicleConditionView($restrictedCritairTypes);
                        }
                    } elseif (VehicleTypeEnum::DIMENSIONS->value === $restrictedVehicleType) {
                        $vehicleConditions[] = new DatexVehicleConditionView(
                            vehicleType: $restrictedVehicleType,
                            maxWidth: $vehicleSet->getMaxWidth(),
                            maxLength: $vehicleSet->getMaxLength(),
                            maxHeight: $vehicleSet->getMaxHeight(),
                        );
                    } elseif (VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value === $restrictedVehicleType) {
                        $vehicleConditions[] = new DatexVehicleConditionView(
                            vehicleType: $restrictedVehicleType,
                            maxWeight: $vehicleSet->getHeavyweightMaxWeight(),
                        );
                    } else {
                        $vehicleConditions[] = new DatexVehicleConditionView(
                            vehicleType: $restrictedVehicleType,
                        );
                    }
                }

                foreach ($vehicleSet->getExemptedTypes() as $exemptedVehicleType) {
                    $vehicleConditions[] = new DatexVehicleConditionView($exemptedVehicleType, isExempted: true);
                }

                /** @var Location $location */
                foreach ($measure->getLocations() as $location) {
                    $locationView = new DatexLocationView(
                        roadType: $location->getRoadType(),
                        roadName: $location->getRoadName(),
                        roadNumber: $location->getRoadNumber(),
                        geometry: $location->getGeometry(),
                    );

                    $trafficRegulations[] = new DatexTrafficRegulationView(
                        $measureType,
                        $locationView,
                        $vehicleConditions,
                        $maxSpeed,
                    );
                }
            }

            $regulationOrderViews[] = new RegulationOrderDatexListItemView(
                uuid: $regulationOrder->getUuid(),
                organization: $regulationOrderRecord->getOrganizationName(),
                description: $regulationOrder->getDescription(),
                startDate: $regulationOrder->getStartDate(),
                endDate: $regulationOrder->getEndDate(),
                trafficRegulations: $trafficRegulations,
            );
        }

        return $regulationOrderViews;
    }
}
