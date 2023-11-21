<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetCifsIncidentsQuery;
use App\Application\Regulation\Query\GetCifsIncidentsQueryHandler;
use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
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
        $incident1 = new CifsIncidentView(
            id: '065490cc-45ad-71ad-8000-9196b66c1ba2',
            creationTime: '2023-11-01T00:00:00+00:00',
            type: 'ROAD_CLOSED',
            subType: 'ROAD_BLOCKED_HAZARD',
            street: 'Rue des Arts',
            direction: 'BOTH_DIRECTIONS',
            polyline: '44.028996 1.362275 44.025665 1.359310',
            startTime: '2023-11-02T00:00:00+00:00',
            endTime: '2023-11-06T00:00:00+00:00',
            sourceReference: 'TODO',
            sourceName: 'DiaLog',
            schedule: [],
        );

        $incident2 = new CifsIncidentView(
            id: '065490e7-1738-7b0e-8000-93b5ff772d94',
            creationTime: '2023-11-01T00:00:00+00:00',
            type: 'ROAD_CLOSED',
            subType: 'ROAD_BLOCKED_CONSTRUCTION',
            street: 'Avenue de Fonneuve',
            direction: 'BOTH_DIRECTIONS',
            polyline: '44.028996 1.362275 44.025665 1.359310',
            startTime: '2023-11-02T00:00:00+00:00',
            endTime: '2023-11-06T00:00:00+00:00',
            sourceReference: 'TODO',
            sourceName: 'DiaLog',
            schedule: [
                'everyday' => [['startTime' => '14:00', 'endTime' => '16:00']],
                'monday' => [['startTime' => '03:00', 'endTime' => '06:00'], ['startTime' => '08:00', 'endTime' => '10:00'], ['startTime' => '19:00', 'endTime' => '21:00']],
                'thursday' => [['startTime' => '03:00', 'endTime' => '06:00']],
                'friday' => [['startTime' => '08:00', 'endTime' => '10:00'], ['startTime' => '19:00', 'endTime' => '21:00']],
                'sunday' => [['startTime' => '00:00', 'endTime' => '23:59']],
            ],
        );

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $measure1 = [
            'measureId' => '065490cc-45ad-71ad-8000-9196b66c1ba2',
            'description' => 'Description 1',
            'category' => RegulationOrderCategoryEnum::INCIDENT->value,
            'createdAt' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-01 00:00:00'),
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'regulationOrderStartDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-02 00:00:00'),
            'regulationOrderEndDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-06 00:00:00'),
            'address' => 'Rue des Arts, 82000 Montauban',
            'fromLatitude' => '44.028996',
            'fromLongitude' => '1.362275',
            'toLatitude' => '44.025665',
            'toLongitude' => '1.35931',
            'applicableDays' => null,
            'startTime' => null,
            'endTime' => null,
        ];

        $measure2Fields = [
            'measureId' => '065490e7-1738-7b0e-8000-93b5ff772d94',
            'description' => 'Description 1',
            'category' => RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            'createdAt' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-01 00:00:00'),
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'regulationOrderStartDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-02 00:00:00'),
            'regulationOrderEndDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-06 00:00:00'),
            'address' => 'Avenue de Fonneuve, 82000 Montauban',
            'fromLatitude' => '44.028996',
            'fromLongitude' => '1.362275',
            'toLatitude' => '44.025665',
            'toLongitude' => '1.35931',
        ];

        $measure2Period1TimeSlot1 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'friday'],
            'startTime' => '08:00',
            'endTime' => '10:00',
        ];

        $measure2Period1TimeSlot2 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'friday'],
            'startTime' => '19:00',
            'endTime' => '21:00',
        ];

        $measure2Period2 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'thursday'],
            'startTime' => '03:00',
            'endTime' => '06:00',
        ];

        $measure2Period3 = [
            ...$measure2Fields,
            'applicableDays' => ['sunday'],
            // No time slots = whole day
            'startTime' => null,
            'endTime' => null,
        ];

        $measure2Period4 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'startTime' => '14:00',
            'endTime' => '16:00',
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([
                $measure1,
                $measure2Period1TimeSlot1,
                $measure2Period1TimeSlot2,
                $measure2Period2,
                $measure2Period3,
                $measure2Period4,
            ]);

        $handler = new GetCifsIncidentsQueryHandler($regulationOrderRecordRepository);
        $incidents = $handler(new GetCifsIncidentsQuery());

        $this->assertEquals(
            [$incident1, $incident2],
            $incidents,
        );
    }
}
