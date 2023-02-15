<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQueryHandler;
use App\Application\Regulation\View\DetailLocationView;
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
        $location = new DetailLocationView(
            postalCode: '82000',
            city: 'Montauban',
            roadName: 'Avenue de Fonneuve',
            fromHouseNumber: '695',
            toHouseNumber: '253',
        );

        $startPeriod = new \DateTime('2022-12-07');
        $endPeriod = new \DateTime('2022-12-17');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecord = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            'status' => 'draft',
            'description' => 'Description 1',
            'startPeriod' => $startPeriod,
            'endPeriod' => $endPeriod,
            'postalCode' => $location->postalCode,
            'city' => $location->city,
            'roadName' => $location->roadName,
            'fromHouseNumber' => $location->fromHouseNumber,
            'toHouseNumber' => $location->toHouseNumber,
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
                'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
                'draft',
                'Description 1',
                new PeriodView($startPeriod, $endPeriod),
                new DetailLocationView(
                    $location->postalCode,
                    $location->city,
                    $location->roadName,
                    $location->fromHouseNumber,
                    $location->toHouseNumber,
                ),
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
            'organizationUuid' => 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            'status' => 'draft',
            'description' => 'Description 1',
            'startPeriod' => null,
            'endPeriod' => null,
            'postalCode' => null,
            'city' => null,
            'roadName' => null,
            'fromHouseNumber' => null,
            'toHouseNumber' => null,
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
                'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
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
