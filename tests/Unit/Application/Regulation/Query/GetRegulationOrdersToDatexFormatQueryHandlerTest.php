<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQueryHandler;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Application\Regulation\View\VehicleCharacteristicsView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersToDatexFormatQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $location1 = new DatexLocationView(
            postalCode: '82000',
            city: 'Montauban',
            roadName: 'Avenue de Fonneuve',
            fromHouseNumber: '695',
            fromLongitude: '1.362275',
            fromLatitude: '44.028996',
            toHouseNumber: '253',
            toLongitude: '1.35931',
            toLatitude: '44.025665',
        );

        $location2 = new DatexLocationView(
            postalCode :'44260',
            city :'Savenay',
            roadName :'Route du Grand Brossais',
            fromHouseNumber :'15',
            fromLatitude :'47.347024',
            fromLongitude :'-1.935836',
            toHouseNumber :'37bis',
            toLatitude :'47.347917',
            toLongitude :'-1.930973',
        );

        $startPeriod1 = new \DateTime('2022-12-07');
        $endPeriod1 = new \DateTime('2022-12-17');
        $startPeriod2 = new \DateTime('2022-12-10');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'issuingAuthority' => 'Autorité 1',
            'description' => 'Description 1',
            'startPeriod' => $startPeriod1,
            'endPeriod' => $endPeriod1,
            'postalCode' => $location1->postalCode,
            'city' => $location1->city,
            'roadName' => $location1->roadName,
            'fromHouseNumber' => $location1->fromHouseNumber,
            'fromLatitude' => $location1->fromLatitude,
            'fromLongitude' => $location1->fromLongitude,
            'toHouseNumber' => $location1->toHouseNumber,
            'toLatitude' => $location1->toLatitude,
            'toLongitude' => $location1->toLongitude,
            'maxWeight' => 3.5,
            'maxHeight' => 3,
            'maxWidth' => 2,
            'maxLength' => 10,
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'issuingAuthority' => 'Autorité 2',
            'description' => 'Description 2',
            'startPeriod' => $startPeriod2,
            'endPeriod' => null,
            'postalCode' => $location2->postalCode,
            'city' => $location2->city,
            'roadName' => $location2->roadName,
            'fromHouseNumber' => $location2->fromHouseNumber,
            'fromLatitude' => $location2->fromLatitude,
            'fromLongitude' => $location2->fromLongitude,
            'toHouseNumber' => $location2->toHouseNumber,
            'toLatitude' => $location2->toLatitude,
            'toLongitude' => $location2->toLongitude,
            'maxWeight' => null,
            'maxHeight' => null,
            'maxWidth' => null,
            'maxLength' => null,
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method("findRegulationOrdersForDatexFormat")
            ->willReturn([$regulationOrder1, $regulationOrder2]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals(
            [
                new RegulationOrderDatexListItemView(
                    uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    issuingAuthority: 'Autorité 1',
                    description: 'Description 1',
                    period: new PeriodView($startPeriod1, $endPeriod1),
                    location: $location1,
                    vehicleCharacteristics: new VehicleCharacteristicsView(3.5, 3, 2, 10),
                ),
                new RegulationOrderDatexListItemView(
                    uuid: '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    issuingAuthority: 'Autorité 2',
                    description: 'Description 2',
                    period: new PeriodView($startPeriod2),
                    location: $location2,
                    vehicleCharacteristics: null,
                ),
            ],
            $regulationOrders,
        );
    }
}
