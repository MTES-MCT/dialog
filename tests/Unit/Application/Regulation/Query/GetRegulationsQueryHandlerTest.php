<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationsQueryHandler;
use App\Application\Regulation\View\NamedStreetView;
use App\Application\Regulation\View\NumberedRoadView;
use App\Application\Regulation\View\RawGeoJSONView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\DTO\RegulationListFiltersDTO;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationOrderTypeEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationsQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startDate1 = new \DateTime('2022-12-07');
        $startDate2 = new \DateTime('2022-12-10');

        $rows = [
            [
                'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                'identifier' => 'F02/2023',
                'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
                'startDate' => $startDate1,
                'endDate' => null,
                'nbLocations' => 0,
                'namedStreet' => null,
                'numberedRoad' => null,
                'rawGeoJSON' => 'rawgeojson label',
                'organizationName' => 'DiaLog',
                'organizationUuid' => 'dcab837f-4460-4355-99d5-bf4891c35f8f',
            ],
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'identifier' => 'F01/2023',
                'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
                'startDate' => $startDate2,
                'endDate' => null,
                'nbLocations' => 2,
                'namedStreet' => 'Avenue de Fonneuve#Montauban#82121',
                'numberedRoad' => null,
                'organizationName' => 'DiaLog',
                'organizationUuid' => 'dcab837f-4460-4355-99d5-bf4891c35f8f',
            ],
            [
                'uuid' => 'ef5b3632-8525-41b5-9e84-3116d9089610',
                'identifier' => 'F01/2024',
                'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
                'startDate' => $startDate2,
                'endDate' => null,
                'nbLocations' => 1,
                'namedStreet' => null,
                'numberedRoad' => 'D123#Ardennes',
                'organizationName' => 'DiaLog',
                'organizationUuid' => 'dcab837f-4460-4355-99d5-bf4891c35f8f',
            ],
        ];

        $dto = new RegulationListFiltersDTO();
        $dto->page = 2;
        $dto->pageSize = 12;
        $dto->identifier = '/20';
        $dto->organizationUuid = 'dcab837f-4460-4355-99d5-bf4891c35f8f';
        $dto->regulationOrderType = RegulationOrderTypeEnum::PERMANENT->value;
        $dto->status = RegulationOrderRecordStatusEnum::DRAFT->value;

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findAllRegulations')
            ->with($dto)
            ->willReturn([
                'count' => 3,
                'items' => $rows,
            ]);

        $handler = new GetRegulationsQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationsQuery($dto));

        $pagination = new Pagination(
            [
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'F02/2023',
                    RegulationOrderRecordStatusEnum::DRAFT->value,
                    0,
                    'DiaLog',
                    'dcab837f-4460-4355-99d5-bf4891c35f8f',
                    new RawGeoJSONView('rawgeojson label'),
                    $startDate1,
                    null,
                ),
                new RegulationOrderListItemView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'F01/2023',
                    RegulationOrderRecordStatusEnum::DRAFT->value,
                    2,
                    'DiaLog',
                    'dcab837f-4460-4355-99d5-bf4891c35f8f',
                    new NamedStreetView(
                        cityCode: '82121',
                        cityLabel: 'Montauban',
                        roadName: 'Avenue de Fonneuve',
                    ),
                    $startDate2,
                    null,
                ),
                new RegulationOrderListItemView(
                    'ef5b3632-8525-41b5-9e84-3116d9089610',
                    'F01/2024',
                    RegulationOrderRecordStatusEnum::DRAFT->value,
                    1,
                    'DiaLog',
                    'dcab837f-4460-4355-99d5-bf4891c35f8f',
                    new NumberedRoadView(
                        administrator: 'Ardennes',
                        roadNumber: 'D123',
                    ),
                    $startDate2,
                    null,
                ),
            ],
            3,
            2,
            12,
        );

        $this->assertEquals($pagination, $regulationOrders);
    }
}
