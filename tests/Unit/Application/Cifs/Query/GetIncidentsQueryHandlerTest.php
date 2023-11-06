<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Cifs\Query;

use App\Application\Cifs\Query\GetIncidentsQuery;
use App\Application\Cifs\Query\GetIncidentsQueryHandler;
use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetIncidentsQueryHandlerTest extends TestCase
{
    public function testGetAllEmpty(): void
    {
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([]);

        $handler = new GetIncidentsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetIncidentsQuery());

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
                'monday' => [['startTime' => '03:00', 'endTime' => '06:00'], ['startTime' => '08:00', 'endTime' => '22:00']],
                'thursday' => [['startTime' => '03:00', 'endTime' => '06:00']],
                'friday' => [['startTime' => '08:00', 'endTime' => '22:00']],
            ],
        );

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $row1 = [
            'measureId' => '065490cc-45ad-71ad-8000-9196b66c1ba2',
            'description' => 'Description 1',
            'category' => RegulationOrderCategoryEnum::INCIDENT->value,
            'createdAt' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-01 00:00:00'),
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'startDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-02 00:00:00'),
            'endDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-06 00:00:00'),
            'address' => 'Rue des Arts, 82000 Montauban',
            'fromLatitude' => '44.028996',
            'fromLongitude' => '1.362275',
            'toLatitude' => '44.025665',
            'toLongitude' => '1.35931',
            'applicableDays' => [],
        ];

        $measure2Fields = [
            'measureId' => '065490e7-1738-7b0e-8000-93b5ff772d94',
            'description' => 'Description 1',
            'category' => RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            'createdAt' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-01 00:00:00'),
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'startDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-02 00:00:00'),
            'endDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-06 00:00:00'),
            'address' => 'Avenue de Fonneuve, 82000 Montauban',
            'fromLatitude' => '44.028996',
            'fromLongitude' => '1.362275',
            'toLatitude' => '44.025665',
            'toLongitude' => '1.35931',
        ];

        $row2 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'friday'],
            'startTime' => '08:00',
            'endTime' => '22:00',
        ];

        $row3 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'thursday'],
            'startTime' => '03:00',
            'endTime' => '06:00',
        ];

        $row4 = [
            ...$measure2Fields,
            'applicableDays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'startTime' => '14:00',
            'endTime' => '16:00',
        ];

        $row5 = [
            'measureId' => '06549108-6543-7332-8000-0149b3b7af86',
            'description' => 'Full street -- ignored',
            'category' => RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            'createdAt' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-01 00:00:00'),
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'startDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-02 00:00:00'),
            'endDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-06 00:00:00'),
            'address' => 'Avenue de Fonneuve, 82000 Montauban',
            'fromLatitude' => null,
            'fromLongitude' => null,
            'toLatitude' => null,
            'toLongitude' => null,
            'applicableDays' => null,
        ];

        $row6 = [
            'measureId' => '06549110-4aac-7379-8000-54b37bad8956',
            'description' => 'Full city -- ignored',
            'category' => RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            'createdAt' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-01 00:00:00'),
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'startDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-02 00:00:00'),
            'endDate' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-11-06 00:00:00'),
            'address' => '82000 Montauban',
            'fromLatitude' => '44.028996',
            'fromLongitude' => '1.362275',
            'toLatitude' => '44.025665',
            'toLongitude' => '1.35931',
            'applicableDays' => null,
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForCifsIncidentFormat')
            ->willReturn([$row1, $row2, $row3, $row4, $row5, $row6]);

        $handler = new GetIncidentsQueryHandler($regulationOrderRecordRepository);
        $incidents = $handler(new GetIncidentsQuery());

        $this->assertEquals(
            [$incident1, $incident2],
            $incidents,
        );
    }
}
