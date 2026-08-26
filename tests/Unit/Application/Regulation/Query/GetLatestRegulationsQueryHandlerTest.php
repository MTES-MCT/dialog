<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetLatestRegulationsQuery;
use App\Application\Regulation\Query\GetLatestRegulationsQueryHandler;
use App\Application\Regulation\View\NamedStreetView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetLatestRegulationsQueryHandlerTest extends TestCase
{
    public function testGetLatest(): void
    {
        $organizationUuids = ['dcab837f-4460-4355-99d5-bf4891c35f8f'];
        $startDate = '2022-12-07';

        $rows = [
            [
                'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                'identifier' => 'F02/2023',
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
                'source' => RegulationOrderRecordSourceEnum::DIALOG->value,
                'overallStartDate' => $startDate,
                'overallEndDate' => null,
                'nbLocations' => 2,
                'namedStreet' => 'Avenue de Fonneuve#Montauban#82121',
                'numberedRoad' => null,
                'organizationName' => 'DiaLog',
                'organizationUuid' => 'dcab837f-4460-4355-99d5-bf4891c35f8f',
            ],
        ];

        $repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findLatestRegulations')
            ->with($organizationUuids, 4)
            ->willReturn($rows);

        $handler = new GetLatestRegulationsQueryHandler($repository);
        $regulations = $handler(new GetLatestRegulationsQuery($organizationUuids));

        $this->assertEquals(
            [
                new RegulationOrderListItemView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'F02/2023',
                    RegulationOrderRecordStatusEnum::PUBLISHED->value,
                    RegulationOrderRecordSourceEnum::DIALOG,
                    2,
                    'DiaLog',
                    'dcab837f-4460-4355-99d5-bf4891c35f8f',
                    new NamedStreetView(
                        cityCode: '82121',
                        cityLabel: 'Montauban',
                        roadName: 'Avenue de Fonneuve',
                    ),
                    new \DateTimeImmutable($startDate),
                    null,
                ),
            ],
            $regulations,
        );
    }
}
