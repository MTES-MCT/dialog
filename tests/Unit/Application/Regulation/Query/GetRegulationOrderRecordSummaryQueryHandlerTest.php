<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQueryHandler;
use App\Application\Regulation\View\ListItemLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Application\Regulation\View\VehicleCharacteristicsView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderRecordSummaryQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $location = new ListItemLocationView(
            city: 'Montauban',
            roadName: 'Avenue de Fonneuve',
        );

        $startPeriod = new \DateTime('2022-12-07');
        $endPeriod = new \DateTime('2022-12-17');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'status' => 'draft',
            'description' => 'Description 1',
            'startPeriod' => $startPeriod,
            'endPeriod' => $endPeriod,
            'city' => $location->city,
            'roadName' => $location->roadName,
            'maxWeight' => 3.5,
            'maxHeight' => 3,
            'maxWidth' => 2,
            'maxLength' => 10,
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("findOneForSummary")
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'draft',
                'Description 1',
                new PeriodView($startPeriod, $endPeriod),
                new ListItemLocationView($location->roadName, $location->city),
                new VehicleCharacteristicsView(3.5, 3, 2, 10),
            ),
            $regulationOrders,
        );
    }

    public function testGetOneWithoutExtraData(): void
    {
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'status' => 'draft',
            'description' => 'Description 1',
            'startPeriod' => null,
            'endPeriod' => null,
            'city' => null,
            'roadName' => null,
            'maxWeight' => null,
            'maxHeight' => null,
            'maxWidth' => null,
            'maxLength' => null,
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("findOneForSummary")
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderRecordSummaryView(
                '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'draft',
                'Description 1',
                null,
                null,
                null,
            ),
            $regulationOrders,
        );
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("findOneForSummary")
            ->willReturn(null);

        $handler = new GetRegulationOrderRecordSummaryQueryHandler($regulationOrderRecordRepository);
        $handler(new GetRegulationOrderRecordSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
