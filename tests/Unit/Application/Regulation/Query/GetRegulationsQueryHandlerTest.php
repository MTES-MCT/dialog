<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
use App\Application\Regulation\View\ListItemLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startDate1 = new \DateTime('2022-12-07');
        $startTime1 = new \DateTime('22:00');
        $endDate1 = new \DateTime('2022-12-17');
        $endTime1 = new \DateTime('06:00');
        $startDate2 = new \DateTime('2022-12-10');
        $organization = $this->createMock(Organization::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'startDate' => $startDate1,
            'startTime' => $startTime1,
            'endDate' => $endDate1,
            'endTime' => $endTime1,
            'status' => 'draft',
            'city' => 'Savenay',
            'roadName' => 'Rue de Prince Bois',
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'startDate' => $startDate2,
            'startTime' => null,
            'endDate' => null,
            'endTime' => null,
            'status' => 'draft',
            'city' => 'Savenay',
            'roadName' => 'Rue du Lac',
        ];
        $regulationOrder3 = [
            'uuid' => 'c421193a-5437-431a-9228-db6288d36a16',
            'startDate' => null,
            'startTime' => null,
            'endDate' => null,
            'endTime' => null,
            'status' => 'draft',
            'city' => null,
            'roadName' => null,
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("findRegulationsByOrganization")
            ->with($organization, 20, 1, 'draft')
            ->willReturn([$regulationOrder1, $regulationOrder2, $regulationOrder3]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("countRegulationsByOrganization")
            ->with($organization, 'draft')
            ->willReturn(3);

        $handler = new GetRegulationsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationsQuery($organization, 20, 1, 'draft'));

        $pagination = new Pagination(
            [
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'draft',
                    new ListItemLocationView('Rue de Prince Bois', 'Savenay'),
                    new PeriodView($startDate1, $startTime1, $endDate1, $endTime1),
                ),
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'draft',
                    new ListItemLocationView('Rue du Lac', 'Savenay'),
                    new PeriodView($startDate2, null, null, null),
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
