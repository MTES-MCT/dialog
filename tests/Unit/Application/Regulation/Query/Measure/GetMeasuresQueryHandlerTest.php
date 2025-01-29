<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Measure;

use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQueryHandler;
use App\Application\Regulation\View\DailyRangeView;
use App\Application\Regulation\View\Measure\LocationView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Application\Regulation\View\Measure\NamedStreetView;
use App\Application\Regulation\View\Measure\NumberedRoadView;
use App\Application\Regulation\View\Measure\StorageAreaView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\TimeSlotView;
use App\Application\Regulation\View\VehicleSetView;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\RoadSideEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\StorageArea;
use App\Domain\Regulation\Measure;
use App\Infrastructure\Persistence\Doctrine\Repository\Regulation\MeasureRepository;
use PHPUnit\Framework\TestCase;

final class GetMeasuresQueryHandlerTest extends TestCase
{
    public function testGetMeasures(): void
    {
        $startTime = new \DateTime('2022-12-07 08:00:00');
        $endTime = new \DateTime('2022-12-17 19:00:00');

        $daysRange1 = [
            [
                'firstDay' => ApplicableDayEnum::MONDAY->value,
                'lastDay' => ApplicableDayEnum::WEDNESDAY->value,
            ],
            [
                'firstDay' => ApplicableDayEnum::FRIDAY->value,
                'lastDay' => ApplicableDayEnum::FRIDAY->value,
            ],
        ];
        $daysRange2 = [
            [
                'firstDay' => ApplicableDayEnum::MONDAY->value,
                'lastDay' => ApplicableDayEnum::MONDAY->value,
            ],
        ];

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::once())
            ->method('getRestrictedTypes')
            ->willReturn(['heavyGoodsVehicle', 'critair', 'other']);
        $vehicleSet
            ->expects(self::once())
            ->method('getCritairTypes')
            ->willReturn(['critair2', 'critair3']);
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getOtherRestrictedTypeText')
            ->willReturn('Matières dangereuses');
        $vehicleSet
            ->expects(self::once())
            ->method('getExemptedTypes')
            ->willReturn(['commercial', 'pedestrians', 'other']);
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getOtherExemptedTypeText')
            ->willReturn('Convois exceptionnels');
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getHeavyweightMaxWeight')
            ->willReturn(3.5);
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getMaxWidth')
            ->willReturn(2.0);
        $vehicleSet
            ->expects(self::exactly(1))
            ->method('getMaxLength')
            ->willReturn(null);
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getMaxHeight')
            ->willReturn(2.4);

        $timeSlot1 = $this->createMock(TimeSlot::class);
        $timeSlot1
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $timeSlot1
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn($endTime);

        $dailyRange1 = $this->createMock(DailyRange::class);
        $dailyRange1
            ->expects(self::once())
            ->method('getDaysRanges')
            ->willReturn($daysRange1);

        $dailyRange2 = $this->createMock(DailyRange::class);
        $dailyRange2
            ->expects(self::once())
            ->method('getDaysRanges')
            ->willReturn($daysRange2);

        $period1 = $this->createMock(Period::class);
        $period1
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([$timeSlot1]);
        $period1
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange1);
        $period1
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn($startTime);
        $period1
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn($endTime);
        $period1
            ->expects(self::once())
            ->method('getRecurrenceType')
            ->willReturn('certainDays');

        $period2 = $this->createMock(Period::class);
        $period2
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange2);
        $period2
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn($startTime);
        $period2
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn($endTime);
        $period2
            ->expects(self::once())
            ->method('getRecurrenceType')
            ->willReturn('certainDays');

        $location1 = $this->createMock(Location::class);
        $namedStreet = $this->createMock(NamedStreet::class);
        $location1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('12fca512-dee7-4b3d-9c86-59b03a88d8d2');
        $location1
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn('lane');
        $location1
            ->expects(self::once())
            ->method('getNamedStreet')
            ->willReturn($namedStreet);
        $namedStreet
            ->expects(self::never())
            ->method('getCityCode');
        $namedStreet
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn('Montauban');
        $namedStreet
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Avenue de Fonneuve');
        $namedStreet
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn('95');
        $namedStreet
            ->expects(self::once())
            ->method('getFromRoadName')
            ->willReturn(null);
        $namedStreet
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn('253');
        $namedStreet
            ->expects(self::once())
            ->method('getToRoadName')
            ->willReturn(null);

        $location2 = $this->createMock(Location::class);
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $location2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('9b80de91-a68e-45a1-b516-77d667c55d53');
        $location2
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::NATIONAL_ROAD->value);
        $location2
            ->expects(self::once())
            ->method('getNumberedRoad')
            ->willReturn($numberedRoad);
        $numberedRoad
            ->expects(self::once())
            ->method('getAdministrator')
            ->willReturn('DIR Sud-Ouest');
        $numberedRoad
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn('N176');
        $numberedRoad
            ->expects(self::once())
            ->method('getFromPointNumber')
            ->willReturn('24');
        $numberedRoad
            ->expects(self::once())
            ->method('getFromSide')
            ->willReturn(RoadSideEnum::D->value);
        $numberedRoad
            ->expects(self::once())
            ->method('getFromAbscissa')
            ->willReturn(0);
        $numberedRoad
            ->expects(self::once())
            ->method('getToPointNumber')
            ->willReturn('28');
        $numberedRoad
            ->expects(self::once())
            ->method('getToSide')
            ->willReturn(RoadSideEnum::D->value);
        $numberedRoad
            ->expects(self::once())
            ->method('getToAbscissa')
            ->willReturn(0);

        $storageArea = $this->createMock(StorageArea::class);
        $storageArea
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Aire de stockage 1');
        $location2
            ->expects(self::once())
            ->method('getStorageArea')
            ->willReturn($storageArea);

        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('2c85cbb4-cce4-460b-9e68-e8fc9de2c0ea');
        $measure
            ->expects(self::once())
            ->method('getType')
            ->willReturn('noEntry');
        $measure
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period1, $period2]);
        $measure
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($vehicleSet);
        $measure
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location1, $location2]);

        $measureRepository = $this->createMock(MeasureRepository::class);
        $measureRepository
            ->expects(self::once())
            ->method('findByRegulationOrderRecordUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([$measure]);

        $handler = new GetMeasuresQueryHandler($measureRepository);
        $summary = $handler(new GetMeasuresQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            [
                new MeasureView(
                    '2c85cbb4-cce4-460b-9e68-e8fc9de2c0ea',
                    'noEntry',
                    [
                        new PeriodView('certainDays', $startTime, $endTime, new DailyRangeView($daysRange1), [new TimeSlotView($startTime, $endTime)]),
                        new PeriodView('certainDays', $startTime, $endTime, new DailyRangeView($daysRange2), []),
                    ],
                    new VehicleSetView(
                        [
                            ['name' => 'Matières dangereuses', 'isOther' => true],
                            ['name' => 'critair2'],
                            ['name' => 'critair3'],
                        ],
                        [
                            ['name' => 'commercial'],
                            ['name' => 'pedestrians'],
                            ['name' => 'Convois exceptionnels', 'isOther' => true],
                        ],
                        [
                            ['name' => 'weight', 'value' => 3.5],
                            ['name' => 'width', 'value' => 2],
                            ['name' => 'height', 'value' => 2.4],
                        ],
                    ),
                    null,
                    [
                        new LocationView(
                            uuid: '12fca512-dee7-4b3d-9c86-59b03a88d8d2',
                            roadType: 'lane',
                            namedStreet: new NamedStreetView(
                                cityLabel: 'Montauban',
                                roadName: 'Avenue de Fonneuve',
                                fromHouseNumber: '95',
                                fromRoadName: null,
                                toHouseNumber: '253',
                                toRoadName: null,
                            ),
                        ),
                        new LocationView(
                            uuid: '9b80de91-a68e-45a1-b516-77d667c55d53',
                            roadType: RoadTypeEnum::NATIONAL_ROAD->value,
                            numberedRoad: new NumberedRoadView(
                                administrator: 'DIR Sud-Ouest',
                                roadNumber: 'N176',
                                fromPointNumber: '24',
                                fromSide: 'D',
                                fromAbscissa: 0,
                                toPointNumber: '28',
                                toSide: 'D',
                                toAbscissa: 0,
                            ),
                            storageArea: new StorageAreaView('Aire de stockage 1'),
                        ),
                    ],
                ),
            ],
            $summary,
        );
    }
}
