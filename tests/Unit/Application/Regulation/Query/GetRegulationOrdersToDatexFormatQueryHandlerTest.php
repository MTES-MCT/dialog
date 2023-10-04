<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQueryHandler;
use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersToDatexFormatQueryHandlerTest extends TestCase
{
    public function testGetAllEmpty(): void
    {
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForDatexFormat')
            ->willReturn([]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals([], $regulationOrders);
    }

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

        $location3 = new DatexLocationView(
            address: '19 Rue Gabriel Péri, 78800 Houilles',
            fromHouseNumber : '16',
            fromLatitude : '49.347054',
            fromLongitude : '-1.935836',
            toHouseNumber : '37bis',
            toLatitude : '47.347917',
            toLongitude : '-1.930973',
        );

        $startDate1 = new \DateTime('2022-12-07');
        $endDate1 = new \DateTime('2022-12-17');
        $startDate2 = new \DateTime('2022-12-10');
        $startDate3 = new \DateTime('2023-12-12');
        $endDate3 = new \DateTime('2023-12-17');

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
            'maxSpeed' => null,
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'restrictedVehicleTypes' => [VehicleTypeEnum::CRITAIR->value],
            'restrictedCritairTypes' => [CritairEnum::CRITAIR_3->value, CritairEnum::CRITAIR_4->value],
            'exemptedVehicleTypes' => null,
            'heavyweightMaxWeight' => null,
            'heavyweightMaxWidth' => null,
            'heavyweightMaxLength' => null,
            'heavyweightMaxHeight' => null,
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
            'maxSpeed' => null,
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'restrictedVehicleTypes' => [],
            'exemptedVehicleTypes' => null,
            'heavyweightMaxWeight' => null,
            'heavyweightMaxWidth' => null,
            'heavyweightMaxLength' => null,
            'heavyweightMaxHeight' => null,
            'restrictedCritairTypes' => null,
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
            'maxSpeed' => null,
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'restrictedVehicleTypes' => ['heavyGoodsVehicle'],
            'exemptedVehicleTypes' => ['commercial'],
            'heavyweightMaxWeight' => 3.5,
            'heavyweightMaxWidth' => 2,
            'heavyweightMaxLength' => 12,
            'heavyweightMaxHeight' => 2.4,
            'restrictedCritairTypes' => null,
        ];
        $row3 = [
            'uuid' => '12410fb8-a2b9-4449-a7d5-a4f409807f99',
            'organizationName' => 'Autorité 3',
            'description' => 'Description 3',
            'startDate' => $startDate3,
            'endDate' => $endDate3,
            'address' => $location3->address,
            'fromHouseNumber' => $location3->fromHouseNumber,
            'fromLatitude' => $location3->fromLatitude,
            'fromLongitude' => $location3->fromLongitude,
            'toHouseNumber' => $location3->toHouseNumber,
            'toLatitude' => $location3->toLatitude,
            'toLongitude' => $location3->toLongitude,
            'maxSpeed' => 50,
            'type' => MeasureTypeEnum::SPEED_LIMITATION->value,
            'restrictedVehicleTypes' => [VehicleTypeEnum::HAZARDOUS_MATERIALS->value],
            'exemptedVehicleTypes' => null,
            'heavyweightMaxWeight' => null,
            'heavyweightMaxWidth' => null,
            'heavyweightMaxLength' => null,
            'heavyweightMaxHeight' => null,
            'restrictedCritairTypes' => null,
        ];

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForDatexFormat')
            ->willReturn([$row1, $row1bis, $row2, $row3]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());
        $hazardousMaterials = new DatexVehicleConditionView('hazardousMaterials');
        $hazardousMaterials->isDangerousSubstances = true;

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
                            type: 'noEntry',
                            location: $location1,
                            vehicleConditions: [
                                new DatexVehicleConditionView('critair3'),
                                new DatexVehicleConditionView('critair4'),
                            ],
                        ),
                        new DatexTrafficRegulationView(
                            type: 'noEntry',
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
                            type: 'noEntry',
                            location: $location2,
                            vehicleConditions: [
                                new DatexVehicleConditionView(
                                    'heavyGoodsVehicle',
                                    maxWeight: 3.5,
                                    maxWidth: 2,
                                    maxLength: 12,
                                    maxHeight: 2.4,
                                ),
                                new DatexVehicleConditionView('commercial', isExempted: true),
                            ],
                        ),
                    ],
                ),
                new RegulationOrderDatexListItemView(
                    uuid: '12410fb8-a2b9-4449-a7d5-a4f409807f99',
                    organization: 'Autorité 3',
                    description: 'Description 3',
                    startDate: $startDate3,
                    endDate: $endDate3,
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            type: 'speedLimitation',
                            location: $location3,
                            vehicleConditions: [
                                $hazardousMaterials,
                            ],
                            maxSpeed: 50,
                        ),
                    ],
                ),
            ],
            $regulationOrders,
        );
    }
}
