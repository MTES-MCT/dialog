<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
use App\Application\Regulation\View\LocationView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startDate1 = new \DateTime('2022-12-07');
        $startDate2 = new \DateTime('2022-12-10');
        $organization = $this->createMock(Organization::class);

        $location = new LocationView(
            address: new LocationAddress('82000', 'Montauban', 'Avenue de Fonneuve'),
        );

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'identifier' => 'F01/2023',
            'address' => 'Avenue de Fonneuve 82000 Montauban',
            'startDate' => $startDate1,
            'endDate' => null,
            'status' => 'draft',
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'identifier' => 'F02/2023',
            'address' => null,
            'startDate' => $startDate2,
            'endDate' => null,
            'status' => 'draft',
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationsByOrganization')
            ->with($organization, 20, 1, true)
            ->willReturn([$regulationOrder2, $regulationOrder1]);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countRegulationsByOrganization')
            ->with($organization, true)
            ->willReturn(2);

        $handler = new GetRegulationsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationsQuery($organization, 20, 1, isPermanent: true));

        $pagination = new Pagination(
            [
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'F02/2023',
                    'draft',
                    null,
                    $startDate2,
                    null,
                    null,
                ),
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'F01/2023',
                    'draft',
                    $location,
                    $startDate1,
                    null,
                    null,
                ),
            ], 2, 1, 20,
        );

        $this->assertEquals($pagination, $regulationOrders);
    }
}
