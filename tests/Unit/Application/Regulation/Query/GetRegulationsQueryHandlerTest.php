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
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startDate1 = new \DateTime('2022-12-07');
        $startDate2 = new \DateTime('2022-12-10');
        $organization = $this->createMock(Organization::class);

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getAddress')
            ->willReturn('Avenue de Fonneuve, 82000 Montauban');

        $locations1 = $this->createMock(Collection::class);
        $locations1
            ->expects(self::once())
            ->method('count')
            ->willReturn(2);
        $locations1
            ->expects(self::once())
            ->method('first')
            ->willReturn($location);

        $locations2 = $this->createMock(Collection::class);
        $locations2
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);
        $locations2
            ->expects(self::never())
            ->method('first');

        $regulationOrder1 = $this->createMock(RegulationOrder::class);
        $regulationOrder1
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate1);
            $regulationOrder1
                ->expects(self::once())
                ->method('getIdentifier')
                ->willReturn('F01/2023');
        $regulationOrder1
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn($locations1);

        $regulationOrderRecord1 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder1);
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf');
        $regulationOrderRecord1
            ->expects(self::once())
            ->method('getStatus')
            ->willReturn('draft');

        $regulationOrder2 = $this->createMock(RegulationOrder::class);
        $regulationOrder2
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate2);
        $regulationOrder2
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F02/2023');
        $regulationOrder2
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn($locations2);

        $regulationOrderRecord2 = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder2);
        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('247edaa2-58d1-43de-9d33-9753bf6f4d30');
        $regulationOrderRecord2
            ->expects(self::once())
            ->method('getStatus')
            ->willReturn('draft');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationsByOrganization')
            ->with($organization, 20, 1, true)
            ->willReturn([$regulationOrderRecord2, $regulationOrderRecord1]);

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
                    0,
                    null,
                    $startDate2,
                    null,
                ),
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'F01/2023',
                    'draft',
                    2,
                    new LocationView(
                        address: new LocationAddress('82000', 'Montauban', 'Avenue de Fonneuve'),
                    ),
                    $startDate1,
                    null,
                ),
            ], 2, 1, 20,
        );

        $this->assertEquals($pagination, $regulationOrders);
    }
}
