<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQueryHandler;
use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexValidityConditionView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Condition\VehicleSet;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersToDatexFormatQueryHandlerTest extends TestCase
{
    private $tz;

    protected function setUp(): void
    {
        $this->tz = new \DateTimeZone('Etc/GMT-1');
    }

    public function testGetAllEmpty(): void
    {
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForDatexFormat')
            ->willReturn([]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals([], $regulationOrders);
    }

    public function testGetAll(): void
    {
        $startDate1 = new \DateTime('2022-12-07', $this->tz);
        $endDate1 = new \DateTime('2022-12-17', $this->tz);
        $startDate2 = new \DateTime('2022-12-10', $this->tz);
        $startDate3 = new \DateTime('2023-12-12', $this->tz);
        $endDate3 = new \DateTime('2023-12-17', $this->tz);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord1 = $this->createMock(RegulationOrderRecord::class);

        $regulationOrder1 = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder1);
        $regulationOrder1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('247edaa2-58d1-43de-9d33-9753bf6f4d30');
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getOrganizationName')
            ->willReturn('Autorité 1');
        $regulationOrder1
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description 1');
        $regulationOrder1
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate1);
        $regulationOrder1
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate1);

        $measure1 = $this->createMock(Measure::class);
        $measure1
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);

        $vehicleSet1 = $this->createMock(VehicleSet::class);
        $measure1
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($vehicleSet1);
        $vehicleSet1
            ->expects(self::once())
            ->method('getRestrictedTypes')
            ->willReturn([VehicleTypeEnum::CRITAIR->value]);
        $vehicleSet1
            ->expects(self::once())
            ->method('getCritairTypes')
            ->willReturn([CritairEnum::CRITAIR_3->value, CritairEnum::CRITAIR_4->value]);
        $vehicleSet1
            ->expects(self::never())
            ->method('getMaxHeight');
        $vehicleSet1
            ->expects(self::never())
            ->method('getMaxWidth');
        $vehicleSet1
            ->expects(self::never())
            ->method('getMaxLength');
        $vehicleSet1
            ->expects(self::never())
            ->method('getHeavyweightMaxWeight');
        $vehicleSet1
            ->expects(self::once())
            ->method('getExemptedTypes')
            ->willReturn([]);

        $locationView1 = new DatexLocationView(
            roadType: RoadTypeEnum::LANE->value,
            roadName: 'Avenue de Fonneuve',
            roadNumber: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.362275, 44.028996),
                Coordinates::fromLonLat(1.35931, 44.025665),
            ]),
        );

        $location1 = $this->createMock(Location::class);
        $location1
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($locationView1->roadType);
        $location1
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($locationView1->roadName);
        $location1
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn($locationView1->roadNumber);
        $location1
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($locationView1->geometry);

        $measure1
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location1]);

        $validityView1 = new DatexValidityConditionView(
            new \DateTimeImmutable('2024-01-03 08:00', $this->tz),
            new \DateTimeImmutable('2024-01-10 16:00', $this->tz),
            [
                [
                    'recurringTimePeriods' => [
                        [
                            'startTime' => new \DateTimeImmutable('08:00', $this->tz),
                            'endTime' => new \DateTimeImmutable('16:00', $this->tz),
                        ],
                    ],
                    'recurringDayWeekMonthPeriods' => [],
                ],
            ],
        );

        $period1 = $this->createMock(Period::class);
        $period1
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn($validityView1->overallStartTime);
        $period1
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn($validityView1->overallEndTime);
        $period1
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn(null);
        $timeSlot1 = $this->createMock(TimeSlot::class);
        $timeSlot1
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn($validityView1->validPeriods[0]['recurringTimePeriods'][0]['startTime']);
        $timeSlot1
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn($validityView1->validPeriods[0]['recurringTimePeriods'][0]['endTime']);
        $period1
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([$timeSlot1]);

        $measure1
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period1]);

        $measure2 = $this->createMock(Measure::class);

        $measure2
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);

        $measure2
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn(null);

        $locationView2 = new DatexLocationView(
            roadType: RoadTypeEnum::LANE->value,
            roadName: "Rue de l'Hôtel de Ville",
            roadNumber: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.352126, 44.016833),
                Coordinates::fromLonLat(1.353016, 44.016402),
            ]),
        );

        $location2 = $this->createMock(Location::class);
        $location2
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($locationView2->roadType);
        $location2
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($locationView2->roadName);
        $location2
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn($locationView2->roadNumber);
        $location2
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($locationView2->geometry);

        $measure2
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location2]);

        $validityView2 = new DatexValidityConditionView(
            new \DateTimeImmutable('2024-03-01 00:00:00', $this->tz),
            new \DateTimeImmutable('2024-03-10 23:59:00', $this->tz),
            [
                [
                    'recurringTimePeriods' => [],
                    'recurringDayWeekMonthPeriods' => [['monday', 'tuesday']],
                ],
            ],
        );

        $period2 = $this->createMock(Period::class);
        $period2
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn($validityView2->overallStartTime);
        $period2
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn($validityView2->overallEndTime);
        $dailyRange = $this->createMock(DailyRange::class);
        $dailyRange
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn($validityView2->validPeriods[0]['recurringDayWeekMonthPeriods'][0]);
        $period2
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange);
        $period2
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([]);

        $measure2
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period2]);

        $regulationOrder1
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measure1, $measure2]);

        $regulationOrderRecord2 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrder2 = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder2);
        $regulationOrder2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf');
        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getOrganizationName')
            ->willReturn('Autorité 2');
        $regulationOrder2
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description 2');
        $regulationOrder2
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate2);
        $regulationOrder2
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn(null);

        $measure3 = $this->createMock(Measure::class);
        $measure3
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);

        $vehicleSet3 = $this->createMock(VehicleSet::class);
        $measure3
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($vehicleSet3);
        $vehicleSet3
            ->expects(self::once())
            ->method('getRestrictedTypes')
            ->willReturn([VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::DIMENSIONS->value]);
        $vehicleSet3
            ->expects(self::never())
            ->method('getCritairTypes');
        $vehicleSet3
            ->expects(self::once())
            ->method('getMaxHeight')
            ->willReturn(2.4);
        $vehicleSet3
            ->expects(self::once())
            ->method('getMaxWidth')
            ->willReturn(2.0);
        $vehicleSet3
            ->expects(self::once())
            ->method('getMaxLength')
            ->willReturn(12.0);
        $vehicleSet3
            ->expects(self::once())
            ->method('getHeavyweightMaxWeight')
            ->willReturn(3.5);
        $vehicleSet3
            ->expects(self::once())
            ->method('getExemptedTypes')
            ->willReturn([VehicleTypeEnum::COMMERCIAL->value]);

        $locationView3 = new DatexLocationView(
            roadType: RoadTypeEnum::LANE->value,
            roadName: 'Route du Grand Brossais',
            roadNumber: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
        );

        $location3 = $this->createMock(Location::class);
        $location3
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($locationView3->roadType);
        $location3
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($locationView3->roadName);
        $location3
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn($locationView3->roadNumber);
        $location3
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($locationView3->geometry);

        $measure3
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location3]);

        $regulationOrder2
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measure3]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForDatexFormat')
            ->willReturn([$regulationOrderRecord1, $regulationOrderRecord2]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals(
            [
                new RegulationOrderDatexListItemView(
                    uuid: '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    organization: 'Autorité 1',
                    description: 'Description 1',
                    startDate: $startDate1,
                    endDate: $endDate1,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            type: MeasureTypeEnum::NO_ENTRY->value,
                            locationConditions: [$locationView1],
                            validityConditions: [$validityView1],
                            vehicleConditions: [
                                new DatexVehicleConditionView('critair3'),
                                new DatexVehicleConditionView('critair4'),
                            ],
                        ),
                        new DatexTrafficRegulationView(
                            type: MeasureTypeEnum::NO_ENTRY->value,
                            locationConditions: [$locationView2],
                            validityConditions: [$validityView2],
                            vehicleConditions: [],
                        ),
                    ],
                ),
                new RegulationOrderDatexListItemView(
                    uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    organization: 'Autorité 2',
                    description: 'Description 2',
                    startDate: $startDate2,
                    endDate: null,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            type: 'noEntry',
                            locationConditions: [$locationView3],
                            validityConditions: [],
                            vehicleConditions: [
                                new DatexVehicleConditionView(
                                    'heavyGoodsVehicle',
                                    maxWeight: 3.5,
                                ),
                                new DatexVehicleConditionView(
                                    'dimensions',
                                    maxWidth: 2,
                                    maxLength: 12,
                                    maxHeight: 2.4,
                                ),
                                new DatexVehicleConditionView(VehicleTypeEnum::COMMERCIAL->value, isExempted: true),
                            ],
                        ),
                    ],
                ),
            ],
            $regulationOrders,
        );
    }
}
