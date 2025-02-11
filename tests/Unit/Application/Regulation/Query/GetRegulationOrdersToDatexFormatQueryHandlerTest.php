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
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\StorageArea;
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
            ->method('getSource')
            ->willReturn(RegulationOrderRecordSourceEnum::DIALOG->value);
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder1);
        $uuid1 = '066c603a-ca34-75b9-8000-62c82cc0ed11';
        $regulationOrderRecord1
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn($uuid1);
        $regulationOrder1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('247edaa2-58d1-43de-9d33-9753bf6f4d30');
        $regulationOrder1
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2024');
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getOrganizationName')
            ->willReturn('Autorité 1');
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getOrganizationUuid')
            ->willReturn('56456ff6-7e1c-4d24-aa09-9c650d7f6115');
        $regulationOrder1
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('Title 1');

        $measure1 = $this->createMock(Measure::class);
        $measure1
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);
        $measure1
            ->expects(self::once())
            ->method('getMaxSpeed')
            ->willReturn(null);

        $vehicleSet1 = $this->createMock(VehicleSet::class);
        $measure1
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($vehicleSet1);
        $vehicleSet1
            ->expects(self::once())
            ->method('getRestrictedTypes')
            ->willReturn([VehicleTypeEnum::CRITAIR->value, VehicleTypeEnum::OTHER->value]);
        $vehicleSet1
            ->expects(self::once())
            ->method('getCritairTypes')
            ->willReturn([CritairEnum::CRITAIR_3->value, CritairEnum::CRITAIR_4->value]);
        $vehicleSet1
            ->expects(self::once())
            ->method('getOtherRestrictedtypeText')
            ->willReturn('Trottinettes');
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
            rawGeoJSONLabel: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.362275, 44.028996),
                Coordinates::fromLonLat(1.35931, 44.025665),
            ]),
        );

        $location1 = $this->createMock(Location::class);
        $namedStreet1 = $this->createMock(NamedStreet::class);
        $location1
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($locationView1->roadType);
        $location1
            ->expects(self::once())
            ->method('getNamedStreet')
            ->willReturn($namedStreet1);
        $namedStreet1
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($locationView1->roadName);
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
            ->method('getMaxSpeed')
            ->willReturn(null);

        $measure2
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn(null);

        $locationView2 = new DatexLocationView(
            roadType: RoadTypeEnum::LANE->value,
            roadName: "Rue de l'Hôtel de Ville",
            roadNumber: null,
            rawGeoJSONLabel: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.352126, 44.016833),
                Coordinates::fromLonLat(1.353016, 44.016402),
            ]),
        );

        $location2 = $this->createMock(Location::class);
        $namedStreet2 = $this->createMock(NamedStreet::class);
        $location2
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($locationView2->roadType);
        $location2
            ->expects(self::once())
            ->method('getNamedStreet')
            ->willReturn($namedStreet2);
        $namedStreet2
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($locationView2->roadName);
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

        $regulationOrderRecord2WinterMaintenance = $this->createMock(RegulationOrderRecord::class);
        $regulationOrder2 = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord2WinterMaintenance
            ->expects(self::once())
            ->method('getSource')
            ->willReturn(RegulationOrderRecordSourceEnum::DIALOG->value);
        $regulationOrderRecord2WinterMaintenance
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder2);
        $uuid2 = '066c603b-c507-75fd-8000-66acdc0f7ba1';
        $regulationOrderRecord2WinterMaintenance
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn($uuid2);
        $regulationOrder2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf');
        $regulationOrder2
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F02/2024');
        $regulationOrderRecord2WinterMaintenance
            ->expects(self::once())
            ->method('getOrganizationName')
            ->willReturn('Autorité 2');
        $regulationOrderRecord2WinterMaintenance
            ->expects(self::once())
            ->method('getOrganizationUuid')
            ->willReturn('df1895bf-17af-4d68-adbd-02a7110d3b29');
        $regulationOrder2
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('Arrêté viabilité hivernale');

        $winterMaintenanceMeasure = $this->createMock(Measure::class);
        $winterMaintenanceMeasure
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);
        $winterMaintenanceMeasure
            ->expects(self::once())
            ->method('getMaxSpeed')
            ->willReturn(null);

        $winterMaintenanceVehicleSet = $this->createMock(VehicleSet::class);
        $winterMaintenanceMeasure
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($winterMaintenanceVehicleSet);
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getRestrictedTypes')
            ->willReturn([VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::DIMENSIONS->value]);
        $winterMaintenanceVehicleSet
            ->expects(self::never())
            ->method('getCritairTypes');
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getMaxHeight')
            ->willReturn(2.4);
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getMaxWidth')
            ->willReturn(2.0);
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getMaxLength')
            ->willReturn(12.0);
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getHeavyweightMaxWeight')
            ->willReturn(3.5);
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getExemptedTypes')
            ->willReturn([VehicleTypeEnum::COMMERCIAL->value, VehicleTypeEnum::OTHER->value, VehicleTypeEnum::ROAD_MAINTENANCE_OR_CONSTRUCTION->value]);
        $winterMaintenanceVehicleSet
            ->expects(self::once())
            ->method('getOtherExemptedTypeText')
            ->willReturn('Véhicules de service');

        $winterMaintenanceStorageAreaLocationView = new DatexLocationView(
            roadType: RoadTypeEnum::NATIONAL_ROAD->value,
            roadName: null,
            roadNumber: 'N176',
            rawGeoJSONLabel: null,
            geometry: '<storageAreaGeometry>',
        );
        $winterMaintenanceStorageArea = $this->createMock(StorageArea::class);
        $winterMaintenanceStorageArea
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn('N176');
        $winterMaintenanceStorageArea
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn('<storageAreaGeometry>');

        $winterMaintenanceLocationView = new DatexLocationView(
            roadType: RoadTypeEnum::NATIONAL_ROAD->value,
            roadName: null,
            roadNumber: 'N176',
            rawGeoJSONLabel: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
        );
        $winterMaintenanceLocation = $this->createMock(Location::class);
        $winterMaintenanceNationalRoad = $this->createMock(NumberedRoad::class);
        $winterMaintenanceLocation
            ->expects(self::once())
            ->method('getNamedStreet')
            ->willReturn(null);
        $winterMaintenanceLocation
            ->expects(self::once())
            ->method('getNumberedRoad')
            ->willReturn($winterMaintenanceNationalRoad);
        $winterMaintenanceLocation
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::NATIONAL_ROAD->value);
        $winterMaintenanceNationalRoad
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn('N176');
        $winterMaintenanceLocation
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($winterMaintenanceLocationView->geometry);
        $winterMaintenanceLocation
            ->expects(self::once())
            ->method('getStorageArea')
            ->willReturn($winterMaintenanceStorageArea);

        $winterMaintenanceMeasure
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$winterMaintenanceLocation]);

        $winterMaintenanceValidityView = new DatexValidityConditionView(
            new \DateTimeImmutable('2024-01-03 08:00', $this->tz),
            new \DateTimeImmutable('2024-01-10 16:00', $this->tz),
            [],
        );

        $winterMaintenancePeriod = $this->createMock(Period::class);
        $winterMaintenancePeriod
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn($winterMaintenanceValidityView->overallStartTime);
        $winterMaintenancePeriod
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn($winterMaintenanceValidityView->overallEndTime);
        $winterMaintenancePeriod
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn(null);
        $winterMaintenancePeriod
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([]);

        $winterMaintenanceMeasure
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$winterMaintenancePeriod]);

        $regulationOrder2
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$winterMaintenanceMeasure]);

        $regulationOrderRecord3 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrder3 = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord3
            ->expects(self::once())
            ->method('getSource')
            ->willReturn(RegulationOrderRecordSourceEnum::DIALOG->value);
        $regulationOrderRecord3
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder3);
        $uuid3 = '066c6040-ab2d-70d6-8000-1de4ad5ed312';
        $regulationOrderRecord3
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn($uuid3);
        $regulationOrder3
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('12410fb8-a2b9-4449-a7d5-a4f409807f99');
        $regulationOrder3
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F03/2024');
        $regulationOrderRecord3
            ->expects(self::once())
            ->method('getOrganizationName')
            ->willReturn('Autorité 3');
        $regulationOrderRecord3
            ->expects(self::once())
            ->method('getOrganizationUuid')
            ->willReturn('fde674d4-9e82-42f5-841d-13f7d20b43e0');
        $regulationOrder3
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('Description 3');

        $measure4 = $this->createMock(Measure::class);
        $measure4
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::SPEED_LIMITATION->value);
        $measure4
            ->expects(self::once())
            ->method('getMaxSpeed')
            ->willReturn(50);

        $vehicleSet4 = $this->createMock(VehicleSet::class);
        $measure4
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($vehicleSet4);
        $vehicleSet4
            ->expects(self::once())
            ->method('getRestrictedTypes')
            ->willReturn([VehicleTypeEnum::HAZARDOUS_MATERIALS->value]);
        $vehicleSet4
            ->expects(self::never())
            ->method('getCritairTypes');
        $vehicleSet4
            ->expects(self::never())
            ->method('getMaxHeight');
        $vehicleSet4
            ->expects(self::never())
            ->method('getMaxWidth');
        $vehicleSet4
            ->expects(self::never())
            ->method('getMaxLength');
        $vehicleSet4
            ->expects(self::never())
            ->method('getHeavyweightMaxWeight');
        $vehicleSet4
            ->expects(self::once())
            ->method('getExemptedTypes')
            ->willReturn([]);

        $locationView4 = new DatexLocationView(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            roadName: 'D303',
            roadNumber: null,
            rawGeoJSONLabel: null,
            geometry: '{"type":"MultiLineString","coordinates":[[[-1.252483043,43.167928836],[-1.252508262,43.168020621],[-1.252512651,43.168130343],[-1.252507561,43.168211614]],[[-1.252488731,43.168340267],[-1.252486756,43.168430423],[-1.252503915,43.168529732],[-1.25252801,43.168606249],[-1.25258819,43.168671432]]]}',
        );

        $location4 = $this->createMock(Location::class);
        $namedStreet4 = $this->createMock(NamedStreet::class);
        $location4
            ->expects(self::once())
            ->method('getNamedStreet')
            ->willReturn($namedStreet4);

        $location4
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($locationView4->roadType);
        $namedStreet4
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($locationView4->roadName);
        $location4
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($locationView4->geometry);

        $measure4
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location4]);

        $regulationOrder3
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measure4]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForDatexFormat')
            ->willReturn([$regulationOrderRecord1, $regulationOrderRecord2WinterMaintenance, $regulationOrderRecord3]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('getOverallDatesByRegulationUuids')
            ->with([$uuid1, $uuid2, $uuid3])
            ->willReturn([
                $uuid1 => ['uuid' => $uuid1, 'overallStartDate' => $startDate1, 'overallEndDate' => $endDate1],
                $uuid2 => ['uuid' => $uuid2, 'overallStartDate' => $startDate2, 'overallEndDate' => null],
                $uuid3 => ['uuid' => $uuid3, 'overallStartDate' => $startDate3, 'overallEndDate' => $endDate3],
            ]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals(
            [
                new RegulationOrderDatexListItemView(
                    uuid: '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    regulationOrderRecordUuid: '066c603a-ca34-75b9-8000-62c82cc0ed11',
                    regulationId: 'F01/2024#56456ff6-7e1c-4d24-aa09-9c650d7f6115',
                    organization: 'Autorité 1',
                    source: RegulationOrderRecordSourceEnum::DIALOG->value,
                    title: 'Title 1',
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
                                new DatexVehicleConditionView(VehicleTypeEnum::OTHER->value, otherTypeText: 'Trottinettes'),
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
                    regulationOrderRecordUuid: '066c603b-c507-75fd-8000-66acdc0f7ba1',
                    regulationId: 'F02/2024#df1895bf-17af-4d68-adbd-02a7110d3b29',
                    organization: 'Autorité 2',
                    source: RegulationOrderRecordSourceEnum::DIALOG->value,
                    title: 'Arrêté viabilité hivernale',
                    startDate: $startDate2,
                    endDate: null,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            type: 'noEntry',
                            locationConditions: [$winterMaintenanceLocationView],
                            validityConditions: [$winterMaintenanceValidityView],
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
                                new DatexVehicleConditionView(VehicleTypeEnum::OTHER->value, isExempted: true, otherTypeText: 'Véhicules de service'),
                                new DatexVehicleConditionView(VehicleTypeEnum::ROAD_MAINTENANCE_OR_CONSTRUCTION->value, isExempted: true),
                            ],
                        ),
                        new DatexTrafficRegulationView(
                            type: 'storageArea',
                            locationConditions: [$winterMaintenanceStorageAreaLocationView],
                            validityConditions: [$winterMaintenanceValidityView],
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
                                new DatexVehicleConditionView(VehicleTypeEnum::OTHER->value, isExempted: true, otherTypeText: 'Véhicules de service'),
                                new DatexVehicleConditionView(VehicleTypeEnum::ROAD_MAINTENANCE_OR_CONSTRUCTION->value, isExempted: true),
                            ],
                        ),
                    ],
                ),
                new RegulationOrderDatexListItemView(
                    uuid: '12410fb8-a2b9-4449-a7d5-a4f409807f99',
                    regulationOrderRecordUuid: '066c6040-ab2d-70d6-8000-1de4ad5ed312',
                    regulationId: 'F03/2024#fde674d4-9e82-42f5-841d-13f7d20b43e0',
                    organization: 'Autorité 3',
                    source: RegulationOrderRecordSourceEnum::DIALOG->value,
                    title: 'Description 3',
                    startDate: $startDate3,
                    endDate: $endDate3,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            type: MeasureTypeEnum::SPEED_LIMITATION->value,
                            locationConditions: [$locationView4],
                            validityConditions: [],
                            vehicleConditions: [
                                new DatexVehicleConditionView(VehicleTypeEnum::HAZARDOUS_MATERIALS->value),
                            ],
                            maxSpeed: 50,
                        ),
                    ],
                ),
            ],
            $regulationOrders,
        );
    }
}
