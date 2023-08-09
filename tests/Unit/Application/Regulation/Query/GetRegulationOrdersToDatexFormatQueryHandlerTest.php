<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQueryHandler;
use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersToDatexFormatQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $location1 = new DatexLocationView(
            address: 'Avenue de Fonneuve 82000 Montauban',
            fromHouseNumber: '695',
            fromLongitude: '1.362275',
            fromLatitude: '44.028996',
            toHouseNumber: '253',
            toLongitude: '1.35931',
            toLatitude: '44.025665',
        );

        $location1bis = new DatexLocationView(
            address: "Rue de l'Hôtel de Ville 82000 Montauban",
            fromHouseNumber: '30',
            fromLongitude: '1.352126',
            fromLatitude: '44.016833',
            toHouseNumber: '12',
            toLongitude: '1.353016',
            toLatitude: '44.016402',
        );

        $location2 = new DatexLocationView(
            address: 'Route du Grand Brossais 44260 Savenay',
            fromHouseNumber : '15',
            fromLatitude : '47.347024',
            fromLongitude : '-1.935836',
            toHouseNumber : '37bis',
            toLatitude : '47.347917',
            toLongitude : '-1.930973',
        );

        $startDate1 = new \DateTime('2022-12-07');
        $endDate1 = new \DateTime('2022-12-17');
        $startDate2 = new \DateTime('2022-12-10');

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $row1 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'organizationName' => 'Autorité 1',
            'description' => 'Description 1',
            'startDate' => $startDate1,
            'endDate' => $endDate1,
            'address' => $location1->address,
            'fromHouseNumber' => $location1->fromHouseNumber,
            'fromLatitude' => $location1->fromLatitude,
            'fromLongitude' => $location1->fromLongitude,
            'toHouseNumber' => $location1->toHouseNumber,
            'toLatitude' => $location1->toLatitude,
            'toLongitude' => $location1->toLongitude,
            'restrictedVehicleTypes' => [],
            'exemptedVehicleTypes' => null,
        ];
        $row1bis = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'organizationName' => 'Autorité 1',
            'description' => 'Description 1',
            'startDate' => $startDate1,
            'endDate' => $endDate1,
            'address' => $location1bis->address,
            'fromHouseNumber' => $location1bis->fromHouseNumber,
            'fromLatitude' => $location1bis->fromLatitude,
            'fromLongitude' => $location1bis->fromLongitude,
            'toHouseNumber' => $location1bis->toHouseNumber,
            'toLatitude' => $location1bis->toLatitude,
            'toLongitude' => $location1bis->toLongitude,
            'restrictedVehicleTypes' => [],
            'exemptedVehicleTypes' => null,
        ];
        $row2 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'organizationName' => 'Autorité 2',
            'description' => 'Description 2',
            'startDate' => $startDate2,
            'endDate' => null,
            'address' => $location2->address,
            'fromHouseNumber' => $location2->fromHouseNumber,
            'fromLatitude' => $location2->fromLatitude,
            'fromLongitude' => $location2->fromLongitude,
            'toHouseNumber' => $location2->toHouseNumber,
            'toLatitude' => $location2->toLatitude,
            'toLongitude' => $location2->toLongitude,
            'restrictedVehicleTypes' => ['heavyGoodsVehicle'],
            'exemptedVehicleTypes' => ['bus'],
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForDatexFormat')
            ->willReturn([$row1, $row1bis, $row2]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals(
            [
                new RegulationOrderDatexListItemView(
                    uuid: '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    organization: 'Autorité 1',
                    description: 'Description 1',
                    startDate: $startDate1,
                    endDate: $endDate1,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            location: $location1,
                            vehicleConditions: [],
                        ),
                        new DatexTrafficRegulationView(
                            location: $location1bis,
                            vehicleConditions: [],
                        ),
                    ],
                ),
                new RegulationOrderDatexListItemView(
                    uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    organization: 'Autorité 2',
                    description: 'Description 2',
                    startDate: $startDate2,
                    endDate: null,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            location: $location2,
                            vehicleConditions: [
                                new DatexVehicleConditionView('heavyGoodsVehicle'),
                                new DatexVehicleConditionView('bus', isExempted: true),
                            ],
                        ),
                    ],
                ),
            ],
            $regulationOrders,
        );
    }
}
