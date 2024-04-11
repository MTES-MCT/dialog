<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetCifsIncidentsQuery;
use App\Application\Regulation\Query\GetCifsIncidentsQueryHandler;
use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetCifsIncidentsQueryHandlerTest extends TestCase
{
    public function testGetAllEmpty(): void
    {
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([]);

        $handler = new GetCifsIncidentsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetCifsIncidentsQuery());

        $this->assertEquals([], $regulationOrders);
    }

    public function testGetAll(): void
    {
        $polyline1 = '44.0289961 1.362275 44.0256652 1.359310';
        $polyline2 = '44.0256652 1.359310 44.1545432 1.34541242';
        $polyline3 = '44.028996 1.3622753 44.025665 1.3593105';

        $geometry1 = json_encode([
            'type' => 'MultiLineString',
            'coordinates' => [
                [
                    [1.362275, 44.0289961],
                    [1.35931, 44.0256652],
                ],
                [
                    [1.35931, 44.0256652],
                    [1.34541242, 44.1545432],
                ],
            ],
        ]);

        $geometry2 = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [
                    [1.362275, 44.028996],
                    [1.35931, 44.025665],
                ],
            ],
        ]);

        $incident1 = new CifsIncidentView(
            id: '02d5eb61-9ca3-4e67-aacd-726f124382d0#0',
            creationTime: '2023-11-01T00:00:00+00:00',
            type: 'ROAD_CLOSED',
            subType: 'ROAD_BLOCKED_HAZARD',
            street: 'Rue des Arts',
            direction: 'BOTH_DIRECTIONS',
            polyline: $polyline1,
            startTime: '2023-11-02T00:00:00+00:00',
            endTime: '2023-11-06T00:00:00+00:00',
            schedule: [],
        );

        $incident2 = new CifsIncidentView(
            id: '02d5eb61-9ca3-4e67-aacd-726f124382d0#1',
            creationTime: $incident1->creationTime,
            type: $incident1->type,
            subType: $incident1->subType,
            street: $incident1->street,
            direction: $incident1->direction,
            polyline: $polyline2,
            startTime: $incident1->startTime,
            endTime: $incident1->endTime,
            schedule: $incident1->schedule,
        );

        $incident3 = new CifsIncidentView(
            id: '9698b212-705c-4c46-8968-63b5a55a4d66#0',
            creationTime: '2023-11-01T00:00:00+00:00',
            type: 'ROAD_CLOSED',
            subType: 'ROAD_BLOCKED_CONSTRUCTION',
            street: 'Avenue de Fonneuve',
            direction: 'BOTH_DIRECTIONS',
            polyline: $polyline3,
            startTime: '2023-11-02T00:00:00+00:00',
            endTime: '2023-11-06T00:00:00+00:00',
            schedule: [
                'everyday' => [['startTime' => new \DateTimeImmutable('14:00'), 'endTime' => new \DateTimeImmutable('16:00')]],
                'monday' => [['startTime' => new \DateTimeImmutable('03:00'), 'endTime' => new \DateTimeImmutable('06:00')], ['startTime' => new \DateTimeImmutable('08:00'), 'endTime' => new \DateTimeImmutable('10:00')], ['startTime' => new \DateTimeImmutable('19:00'), 'endTime' => new \DateTimeImmutable('21:00')]],
                'thursday' => [['startTime' => new \DateTimeImmutable('03:00'), 'endTime' => new \DateTimeImmutable('06:00')]],
                'friday' => [['startTime' => new \DateTimeImmutable('08:00'), 'endTime' => new \DateTimeImmutable('10:00')], ['startTime' => new \DateTimeImmutable('19:00'), 'endTime' => new \DateTimeImmutable('21:00')]],
                'sunday' => [['startTime' => new \DateTimeImmutable('00:00'), 'endTime' => new \DateTimeImmutable('23:59')]],
            ],
        );

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord1 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn(new \DateTimeImmutable('2023-11-01 00:00:00'));

        $regulationOrder1 = $this->createMock(RegulationOrder::class);
        $regulationOrder1
            ->expects(self::once())
            ->method('getCategory')
            ->willReturn(RegulationOrderCategoryEnum::INCIDENT->value);
        $regulationOrder1
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn(new \DateTimeImmutable('2023-11-02 00:00:00'));
        $regulationOrder1
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn(new \DateTimeImmutable('2023-11-06 00:00:00'));

        $measure1 = $this->createMock(Measure::class);

        $measure1
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([]);

        $location1 = $this->createMock(Location::class);
        $location1
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn('02d5eb61-9ca3-4e67-aacd-726f124382d0');
        $location1
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Rue des Arts');
        $location1
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($geometry1);

        $measure1
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location1]);

        $regulationOrder1
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measure1]);

        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder1);

        $regulationOrderRecord2 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn(new \DateTimeImmutable('2023-11-01 00:00:00'));

        $regulationOrder2 = $this->createMock(RegulationOrder::class);
        $regulationOrder2
            ->expects(self::once())
            ->method('getCategory')
            ->willReturn(RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value);
        $regulationOrder2
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn(new \DateTimeImmutable('2023-11-02 00:00:00'));
        $regulationOrder2
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn(new \DateTimeImmutable('2023-11-06 00:00:00'));

        $measure2 = $this->createMock(Measure::class);

        $period1 = $this->createMock(Period::class);

        $dailyRange1 = $this->createMock(DailyRange::class);
        $dailyRange1
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn([ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::FRIDAY->value]);
        $period1
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange1);

        $timeSlot1 = $this->createMock(TimeSlot::class);
        $timeSlot1
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('08:00'));
        $timeSlot1
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('10:00'));

        $timeSlot2 = $this->createMock(TimeSlot::class);
        $timeSlot2
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('19:00'));
        $timeSlot2
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('21:00'));

        $period1
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([$timeSlot1, $timeSlot2]);

        $period2 = $this->createMock(Period::class);

        $dailyRange2 = $this->createMock(DailyRange::class);
        $dailyRange2
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn([ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::THURSDAY->value]);
        $period2
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange2);

        $timeSlot3 = $this->createMock(TimeSlot::class);
        $timeSlot3
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('03:00'));
        $timeSlot3
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('06:00'));

        $period2
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([$timeSlot3]);

        $period3 = $this->createMock(Period::class);

        $dailyRange3 = $this->createMock(DailyRange::class);
        $dailyRange3
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn([ApplicableDayEnum::SUNDAY->value]);
        $period3
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange3);
        $period3
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([]); // Whole day

        $period4 = $this->createMock(Period::class);

        $dailyRange4 = $this->createMock(DailyRange::class);
        $dailyRange4
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn([
                ApplicableDayEnum::MONDAY->value,
                ApplicableDayEnum::TUESDAY->value,
                ApplicableDayEnum::WEDNESDAY->value,
                ApplicableDayEnum::THURSDAY->value,
                ApplicableDayEnum::FRIDAY->value,
                ApplicableDayEnum::SATURDAY->value,
                ApplicableDayEnum::SUNDAY->value,
            ]);
        $period4
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange4);

        $timeSlot4 = $this->createMock(TimeSlot::class);
        $timeSlot4
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('14:00'));
        $timeSlot4
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('16:00'));
        $period4
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([$timeSlot4]);

        $measure2
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period1, $period2, $period3, $period4]);

        $location2 = $this->createMock(Location::class);
        $location2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('9698b212-705c-4c46-8968-63b5a55a4d66');
        $location2
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Avenue de Fonneuve');
        $location2
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($geometry2);

        $measure2
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location2]);

        $regulationOrder2
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measure2]);

        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder2);

        $regulationOrderRecordRepository
            ->expects(self::exactly(2))
            ->method('convertToCifsPolylines')
            ->withConsecutive([$geometry1], [$geometry2])
            ->willReturnOnConsecutiveCalls([$polyline1, $polyline2], [$polyline3]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([$regulationOrderRecord1, $regulationOrderRecord2]);

        $handler = new GetCifsIncidentsQueryHandler($regulationOrderRecordRepository);
        $incidents = $handler(new GetCifsIncidentsQuery());

        $this->assertEquals(
            [$incident1, $incident2, $incident3],
            $incidents,
        );
    }
}
