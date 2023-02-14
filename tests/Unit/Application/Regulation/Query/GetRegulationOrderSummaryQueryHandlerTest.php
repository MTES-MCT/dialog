<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderSummaryQuery;
use App\Application\Regulation\Query\GetRegulationOrderSummaryQueryHandler;
use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderSummaryView;
use App\Application\Regulation\View\VehicleCharacteristicsView;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderSummaryQueryHandlerTest extends TestCase
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

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $regulationOrderSummary = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
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

        $regulationOrderRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn($regulationOrderSummary);

        $handler = new GetRegulationOrderSummaryQueryHandler($regulationOrderRepository);
        $regulationOrders = $handler(new GetRegulationOrderSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderSummaryView(
                '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
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
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $regulationOrderSummary = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
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

        $regulationOrderRepository
            ->expects(self::once())
            ->method('findOneForSummary')
            ->willReturn($regulationOrderSummary);

        $handler = new GetRegulationOrderSummaryQueryHandler($regulationOrderRepository);
        $regulationOrders = $handler(new GetRegulationOrderSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals(
            new RegulationOrderSummaryView(
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
        $this->expectException(RegulationOrderNotFoundException::class);

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $regulationOrderRepository
            ->expects(self::once())
            ->method("findOneForSummary")
            ->willReturn(null);

        $handler = new GetRegulationOrderSummaryQueryHandler($regulationOrderRepository);
        $handler(new GetRegulationOrderSummaryQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
