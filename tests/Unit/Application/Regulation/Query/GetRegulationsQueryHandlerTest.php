<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
use App\Application\Regulation\View\ListItemLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startPeriod1 = new \DateTime('2022-12-07');
        $endPeriod1 = new \DateTime('2022-12-17');
        $startPeriod2 = new \DateTime('2022-12-10');
        $organization = $this->createMock(Organization::class);

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'startPeriod' => $startPeriod1,
            'endPeriod' => $endPeriod1,
            'status' => 'draft',
            'city' => 'Savenay',
            'roadName' => 'Rue de Prince Bois',
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'startPeriod' => $startPeriod2,
            'endPeriod' => null,
            'status' => 'draft',
            'city' => 'Savenay',
            'roadName' => 'Rue du Lac',
        ];
        $regulationOrder3 = [
            'uuid' => 'c421193a-5437-431a-9228-db6288d36a16',
            'startPeriod' => null,
            'endPeriod' => null,
            'status' => 'draft',
            'city' => null,
            'roadName' => null,
        ];

        $regulationOrderRepository
            ->expects(self::once())
            ->method("findRegulationsByOrganization")
            ->with($organization, 20, 1, 'draft')
            ->willReturn([$regulationOrder1, $regulationOrder2, $regulationOrder3]);

        $regulationOrderRepository
            ->expects(self::once())
            ->method("countRegulationsByOrganization")
            ->with($organization, 'draft')
            ->willReturn(3);

        $handler = new GetRegulationsQueryHandler($regulationOrderRepository);
        $regulationOrders = $handler(new GetRegulationsQuery($organization, 20, 1, 'draft'));

        $pagination = new Pagination(
            [
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'draft',
                    new ListItemLocationView('Rue de Prince Bois', 'Savenay'),
                    new PeriodView($startPeriod1, $endPeriod1),
                ),
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'draft',
                    new ListItemLocationView('Rue du Lac', 'Savenay'),
                    new PeriodView($startPeriod2),
                ),
                new RegulationOrderListItemView(
                    'c421193a-5437-431a-9228-db6288d36a16',
                    'draft',
                    null,
                    null,
                ),
            ], 3, 1, 20,
        );

        $this->assertEquals($pagination, $regulationOrders);
    }
}
