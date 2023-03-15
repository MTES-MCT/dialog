<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
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
        $endDate1 = new \DateTime('2022-12-17');
        $startDate2 = new \DateTime('2022-12-10');
        $organization = $this->createMock(Organization::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'startDate' => $startDate1,
            'endDate' => $endDate1,
            'status' => 'draft',
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'startDate' => $startDate2,
            'endDate' => null,
            'status' => 'draft',
        ];
        $regulationOrder3 = [
            'uuid' => 'c421193a-5437-431a-9228-db6288d36a16',
            'startDate' => null,
            'endDate' => null,
            'status' => 'draft',
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
                    $startDate1,
                    $endDate1,
                ),
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'draft',
                    $startDate2,
                    null,
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
