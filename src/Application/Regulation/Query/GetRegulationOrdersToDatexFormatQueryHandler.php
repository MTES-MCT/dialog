<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexValidityConditionView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrderRecord;
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

        $uuids = [];

        /** @var RegulationOrderRecord $regulationOrderRecord */
        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $uuids[] = $regulationOrderRecord->getUuid();
        }

        $overallDates = $this->repository->getOverallDatesByRegulationUuids($uuids);

        $regulationOrderViews = [];

        /** @var RegulationOrderRecord $regulationOrderRecord */
        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $uuid = $regulationOrderRecord->getUuid();
            $regulationOrder = $regulationOrderRecord->getRegulationOrder();

            $trafficRegulations = [];

            /** @var Measure $measure */
            foreach ($regulationOrder->getMeasures() as $measure) {
                $measureType = $measure->getType();
                $maxSpeed = $measure->getMaxSpeed();
                $vehicleSet = $measure->getVehicleSet();

                $vehicleConditions = [];

                if ($vehicleSet) {
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
                        } elseif (VehicleTypeEnum::OTHER->value === $restrictedVehicleType) {
                            $vehicleConditions[] = new DatexVehicleConditionView(
                                vehicleType: $restrictedVehicleType,
                                otherTypeText: $vehicleSet->getOtherRestrictedTypeText(),
                            );
                        } else {
                            $vehicleConditions[] = new DatexVehicleConditionView(
                                vehicleType: $restrictedVehicleType,
                            );
                        }
                    }

                    foreach ($vehicleSet->getExemptedTypes() as $exemptedVehicleType) {
                        if (VehicleTypeEnum::OTHER->value === $exemptedVehicleType) {
                            $vehicleConditions[] = new DatexVehicleConditionView(
                                vehicleType: $exemptedVehicleType,
                                otherTypeText: $vehicleSet->getOtherExemptedTypeText(),
                                isExempted: true,
                            );
                        } else {
                            $vehicleConditions[] = new DatexVehicleConditionView($exemptedVehicleType, isExempted: true);
                        }
                    }
                }

                $locationConditions = [];

                /** @var Location $location */
                foreach ($measure->getLocations() as $location) {
                    $locationConditions[] = new DatexLocationView(
                        roadType: $location->getRoadType(),
                        roadName: $location->getNamedStreet()?->getRoadName(),
                        roadNumber: $location->getNumberedRoad()?->getRoadNumber(),
                        rawGeoJSONLabel: $location->getRawGeoJSON()?->getLabel(),
                        geometry: $location->getGeometry(),
                    );
                }

                $validityConditions = [];

                foreach ($measure->getPeriods() as $period) {
                    $overallStartTime = $period->getStartDateTime();
                    $overallEndTime = $period->getEndDateTime();

                    $validPeriods = [];
                    $dailyRange = $period->getDailyRange();
                    $timeSlots = $period->getTimeSlots() ?? [];

                    if ($dailyRange || \count($timeSlots) > 0) {
                        $recurringTimePeriods = [];

                        foreach ($timeSlots as $timeSlot) {
                            $recurringTimePeriods[] = ['startTime' => $timeSlot->getStartTime(), 'endTime' => $timeSlot->getEndTime()];
                        }

                        $validPeriods[] = [
                            'recurringTimePeriods' => $recurringTimePeriods,
                            'recurringDayWeekMonthPeriods' => $dailyRange ? [$dailyRange->getApplicableDays()] : [],
                        ];
                    }

                    $validityConditions[] = new DatexValidityConditionView(
                        $overallStartTime,
                        $overallEndTime,
                        $validPeriods,
                    );
                }

                $trafficRegulations[] = new DatexTrafficRegulationView(
                    $measureType,
                    $locationConditions,
                    $vehicleConditions,
                    $validityConditions,
                    $maxSpeed,
                );
            }

            $regulationOrderViews[] = new RegulationOrderDatexListItemView(
                uuid: $regulationOrder->getUuid(),
                regulationOrderRecordUuid: $uuid,
                regulationId: $regulationOrder->getIdentifier() . '#' . $regulationOrderRecord->getOrganizationUuid(),
                organization: $regulationOrderRecord->getOrganizationName(),
                source: $regulationOrderRecord->getSource(),
                description: $regulationOrder->getDescription(),
                startDate: $overallDates[$uuid]['overallStartDate'],
                endDate: $overallDates[$uuid]['overallEndDate'],
                trafficRegulations: $trafficRegulations,
            );
        }

        return $regulationOrderViews;
    }
}
