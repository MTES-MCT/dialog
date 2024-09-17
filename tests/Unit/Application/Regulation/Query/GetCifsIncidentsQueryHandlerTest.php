<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Cifs\PolylineMakerInterface;
use App\Application\Regulation\DTO\CifsFilterSet;
use App\Application\Regulation\Query\GetCifsIncidentsQuery;
use App\Application\Regulation\Query\GetCifsIncidentsQueryHandler;
use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetCifsIncidentsQueryHandlerTest extends TestCase
{
    private $regulationOrderRecordRepository;
    private $polylineMaker;

    protected function setUp(): void
    {
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->polylineMaker = $this->createMock(PolylineMakerInterface::class);
    }

    public function testGetAllEmpty(): void
    {
        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([]);

        $handler = new GetCifsIncidentsQueryHandler($this->regulationOrderRecordRepository, $this->polylineMaker);
        $regulationOrders = $handler(new GetCifsIncidentsQuery());

        $this->assertEquals([], $regulationOrders);
    }

    public function testGetAll(): void
    {
        $polyline1 = '44.0289961 1.362275 44.0256652 1.359310';
        $polyline1Hash = md5($polyline1);
        $polyline2 = '44.0256652 1.359310 44.1545432 1.34541242';
        $polyline2Hash = md5($polyline2);
        $polyline3 = '44.028996 1.3622753 44.025665 1.3593105';
        $polyline3Hash = md5($polyline3);

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

        $period1Id = '0661e7da-0639-7e7a-8000-153b4c23b48b';
        $period2Id = '0661e7ed-1a09-7c77-8000-17281ececeba';
        $period3Id = '0661e7ed-806d-75f0-8000-7107c838edb5';
        $period4Id = '0661e7ed-e549-7e4b-8000-945882a092c4';
        $period5Id = '066e2bff-d436-7f16-8000-57d9f6b99960';

        $incident1 = new CifsIncidentView(
            id: \sprintf('2024T1:02d5eb61-9ca3-4e67-aacd-726f124382d0:%s:0', $polyline1Hash),
            creationTime: new \DateTimeImmutable('2023-11-01T00:00:00+00:00'),
            type: 'ROAD_CLOSED',
            subType: 'ROAD_BLOCKED_HAZARD',
            street: 'Rue des Arts',
            direction: 'BOTH_DIRECTIONS',
            polyline: $polyline1,
            startTime: new \DateTimeImmutable('2023-11-02T00:00:00+00:00'),
            endTime: new \DateTimeImmutable('2023-11-06T00:00:00+00:00'),
            schedule: [],
        );

        $incident2 = new CifsIncidentView(
            id: \sprintf('2024T1:02d5eb61-9ca3-4e67-aacd-726f124382d0:%s:0', $polyline2Hash),
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
            id: \sprintf('2024T2:9698b212-705c-4c46-8968-63b5a55a4d66:%s:%s', $polyline3Hash, $period1Id),
            creationTime: new \DateTimeImmutable('2023-11-01T00:00:00+00:00'),
            type: 'ROAD_CLOSED',
            subType: 'ROAD_BLOCKED_CONSTRUCTION',
            street: 'Avenue de Fonneuve',
            direction: 'BOTH_DIRECTIONS',
            polyline: $polyline3,
            startTime: new \DateTimeImmutable('2023-11-02T00:00:00+00:00'),
            endTime: new \DateTimeImmutable('2023-11-06T00:00:00+00:00'),
            schedule: [
                'monday' => [
                    [
                        'startTime' => new \DateTimeImmutable('08:00'),
                        'endTime' => new \DateTimeImmutable('10:00'),
                    ],
                    [
                        'startTime' => new \DateTimeImmutable('19:00'),
                        'endTime' => new \DateTimeImmutable('21:00'),
                    ],
                ],
                'friday' => [
                    [
                        'startTime' => new \DateTimeImmutable('08:00'),
                        'endTime' => new \DateTimeImmutable('10:00'),
                    ],
                    [
                        'startTime' => new \DateTimeImmutable('19:00'),
                        'endTime' => new \DateTimeImmutable('21:00'),
                    ],
                ],
            ],
        );

        $incident4 = new CifsIncidentView(
            id: \sprintf('2024T2:9698b212-705c-4c46-8968-63b5a55a4d66:%s:%s', $polyline3Hash, $period2Id),
            creationTime: $incident3->creationTime,
            type: $incident3->type,
            subType: $incident3->subType,
            street: $incident3->street,
            direction: $incident3->direction,
            polyline: $incident3->polyline,
            startTime: new \DateTimeImmutable('2023-11-03T00:00:00+00:00'),
            endTime: new \DateTimeImmutable('2023-11-04T23:59:00+00:00'),
            schedule: [
                'monday' => [
                    [
                        'startTime' => new \DateTimeImmutable('03:00'),
                        'endTime' => new \DateTimeImmutable('06:00'),
                    ],
                ],
                'thursday' => [
                    [
                        'startTime' => new \DateTimeImmutable('03:00'),
                        'endTime' => new \DateTimeImmutable('06:00'),
                    ],
                ],
            ],
        );

        $incident5 = new CifsIncidentView(
            id: \sprintf('2024T2:9698b212-705c-4c46-8968-63b5a55a4d66:%s:%s', $polyline3Hash, $period3Id),
            creationTime: $incident3->creationTime,
            type: $incident3->type,
            subType: $incident3->subType,
            street: $incident3->street,
            direction: $incident3->direction,
            polyline: $incident3->polyline,
            startTime: $incident4->startTime,
            endTime: $incident4->endTime,
            schedule: [
                'sunday' => [
                    [
                        'startTime' => new \DateTimeImmutable('00:00'),
                        'endTime' => new \DateTimeImmutable('23:59'),
                    ],
                ],
            ],
        );

        $incident6 = new CifsIncidentView(
            id: \sprintf('2024T2:9698b212-705c-4c46-8968-63b5a55a4d66:%s:%s', $polyline3Hash, $period4Id),
            creationTime: $incident3->creationTime,
            type: $incident3->type,
            subType: $incident3->subType,
            street: $incident3->street,
            direction: $incident3->direction,
            polyline: $incident3->polyline,
            startTime: $incident4->startTime,
            endTime: $incident4->endTime,
            schedule: [
                'everyday' => [
                    [
                        'startTime' => new \DateTimeImmutable('14:00'),
                        'endTime' => new \DateTimeImmutable('16:00'),
                    ],
                ],
            ],
        );

        $incident7 = new CifsIncidentView(
            id: \sprintf('2024T2:9698b212-705c-4c46-8968-63b5a55a4d66:%s:%s', $polyline3Hash, $period5Id),
            creationTime: $incident3->creationTime,
            type: $incident3->type,
            subType: $incident3->subType,
            street: $incident3->street,
            direction: $incident3->direction,
            polyline: $incident3->polyline,
            startTime: $incident4->startTime,
            endTime: $incident4->endTime,
            schedule: [
                'everyday' => [
                    [
                        'startTime' => new \DateTimeImmutable('14:00'),
                        'endTime' => new \DateTimeImmutable('16:00'),
                    ],
                ],
            ],
        );

        $regulationOrderRecord1 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn(new \DateTimeImmutable('2023-11-01 00:00:00'));

        $regulationOrder1 = $this->createMock(RegulationOrder::class);
        $regulationOrder1
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('2024T1');
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
        $namedStreet1 = $this->createMock(NamedStreet::class);
        $location1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('02d5eb61-9ca3-4e67-aacd-726f124382d0');
        $location1
            ->expects(self::exactly(2))
            ->method('getNamedStreet')
            ->willReturn($namedStreet1);
        $namedStreet1
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
            ->method('getIdentifier')
            ->willReturn('2024T2');
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
        $period1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($period1Id);
        $period1
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-02 00:00:00'));
        $period1
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-06 00:00:00'));

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
        $period2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($period2Id);
        $period2
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-03 00:00:00'));
        $period2
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-04 23:59:00'));

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
        $period3
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($period3Id);
        $period3
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-03 00:00:00'));
        $period3
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-04 23:59:00'));

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

        // Daily range with all days
        $period4 = $this->createMock(Period::class);
        $period4
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($period4Id);
        $period4
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-03 00:00:00'));
        $period4
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-04 23:59:00'));

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

        // Time slots without daily range
        $period5 = $this->createMock(Period::class);
        $period5
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($period5Id);
        $period5
            ->expects(self::once())
            ->method('getStartDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-03 00:00:00'));
        $period5
            ->expects(self::once())
            ->method('getEndDateTime')
            ->willReturn(new \DateTimeImmutable('2023-11-04 23:59:00'));
        $period5
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn(null);

        $timeSlot5 = $this->createMock(TimeSlot::class);
        $timeSlot5
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('14:00'));
        $timeSlot5
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('16:00'));
        $period5
            ->expects(self::once())
            ->method('getTimeSlots')
            ->willReturn([$timeSlot5]);

        $measure2
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period1, $period2, $period3, $period4, $period5]);

        $location2 = $this->createMock(Location::class);
        $namedStreet2 = $this->createMock(NamedStreet::class);
        $location2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('9698b212-705c-4c46-8968-63b5a55a4d66');
        $location2
            ->expects(self::exactly(2))
            ->method('getNamedStreet')
            ->willReturn($namedStreet2);
        $namedStreet2
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

        $this->polylineMaker
            ->expects(self::exactly(2))
            ->method('getPolylines')
            ->withConsecutive([$geometry1], [$geometry2])
            ->willReturnOnConsecutiveCalls([$polyline1, $polyline2], [$polyline3]);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([$regulationOrderRecord1, $regulationOrderRecord2]);

        $handler = new GetCifsIncidentsQueryHandler($this->regulationOrderRecordRepository, $this->polylineMaker);
        $incidents = $handler(new GetCifsIncidentsQuery());

        $this->assertEquals(
            [$incident1, $incident2, $incident3, $incident4, $incident5, $incident6, $incident7],
            $incidents,
        );
    }

    public function testFilters(): void
    {
        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->with(['my_source'], ['identifier1'], ['591b83d1-c70d-4b18-85be-091acd73b087'])
            ->willReturn([]); // Don't care, we just test that the filters were passed to the repository method

        $handler = new GetCifsIncidentsQueryHandler(
            $this->regulationOrderRecordRepository,
            $this->polylineMaker,
            new CifsFilterSet(
                allowedSources: ['my_source'],
                excludedIdentifiers: ['identifier1'],
                allowedLocationIds: ['591b83d1-c70d-4b18-85be-091acd73b087'],
            ),
        );
        $incidents = $handler(new GetCifsIncidentsQuery());

        $this->assertEquals([], $incidents);
    }
}
