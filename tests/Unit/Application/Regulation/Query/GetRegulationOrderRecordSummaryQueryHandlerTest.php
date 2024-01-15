<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQueryHandler;
use App\Application\Regulation\View\DailyRangeView;
use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\MeasureView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Application\Regulation\View\TimeSlotView;
use App\Application\Regulation\View\VehicleSetView;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderRecordSummaryQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $startDate = new \DateTime('2022-12-07');
        $endDate = new \DateTime('2022-12-17');

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

        $measure = $this->createMock(Measure::class);
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

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('2c85cbb4-cce4-460b-9e68-e8fc9de2c0ea');
        $location
            ->expects(self::never())
            ->method('getCityCode');
        $location
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn('Montauban');
        $location
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Avenue de Fonneuve');
        $location
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn('95');
        $location
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn('253');
        $location
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measure]);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2023');
        $regulationOrder
            ->expects(self::once())
            ->method('getCategory')
            ->willReturn('other');
        $regulationOrder
            ->expects(self::once())
            ->method('getOtherCategoryText')
            ->willReturn('Other category 1');
        $regulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description 1');
        $regulationOrder
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate);
        $regulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate);
        $regulationOrder
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location]);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf');
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganizationName')
            ->willReturn('DiaLog');
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganizationUuid')
            ->willReturn('a8439603-40f7-4b1e-8a35-cee9e53b98d4');
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getStatus')
            ->willReturn('draft');

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $summary = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                identifier: 'F01/2023',
                organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                organizationName: 'DiaLog',
                status: 'draft',
                category: 'other',
                otherCategoryText: 'Other category 1',
                description: 'Description 1',
                locations: [
                    new DetailLocationView(
                        uuid: '2c85cbb4-cce4-460b-9e68-e8fc9de2c0ea',
                        cityLabel: 'Montauban',
                        roadName: 'Avenue de Fonneuve',
                        fromHouseNumber: '95',
                        toHouseNumber: '253',
                        measures: [
                            new MeasureView(
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
                            ),
                        ],
                    ),
                ],
                startDate: $startDate,
                endDate: $endDate,
            ),
            $summary,
        );
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn(null);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
