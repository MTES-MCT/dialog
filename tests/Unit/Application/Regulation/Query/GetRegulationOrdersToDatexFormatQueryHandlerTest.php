<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQueryHandler;
use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
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
            roadType: RoadTypeEnum::LANE->value,
            roadName: 'Avenue de Fonneuve',
            roadNumber: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.362275, 44.028996),
                Coordinates::fromLonLat(1.35931, 44.025665),
            ]),
        );

        $location1bis = new DatexLocationView(
            roadType: RoadTypeEnum::LANE->value,
            roadName: "Rue de l'Hôtel de Ville",
            roadNumber: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.352126, 44.016833),
                Coordinates::fromLonLat(1.353016, 44.016402),
            ]),
        );

        $location2 = new DatexLocationView(
            roadType: RoadTypeEnum::LANE->value,
            roadName: 'Route du Grand Brossais',
            roadNumber: null,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
        );

        $location3 = new DatexLocationView(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            roadName: 'D303',
            roadNumber: null,
            geometry: '{"type":"MultiLineString","coordinates":[[[-1.252483043,43.167928836],[-1.252508262,43.168020621],[-1.252512651,43.168130343],[-1.252507561,43.168211614]],[[-1.252488731,43.168340267],[-1.252486756,43.168430423],[-1.252503915,43.168529732],[-1.25252801,43.168606249],[-1.25258819,43.168671432]]]}',
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
            'roadType' => $location1->roadType,
            'roadName' => $location1->roadName,
            'roadNumber' => $location1->roadNumber,
            'geometry' => $location1->geometry,
            'maxSpeed' => null,
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'restrictedVehicleTypes' => [VehicleTypeEnum::CRITAIR->value],
            'restrictedCritairTypes' => [CritairEnum::CRITAIR_3->value, CritairEnum::CRITAIR_4->value],
            'exemptedVehicleTypes' => null,
            'heavyweightMaxWeight' => null,
            'maxWidth' => null,
            'maxLength' => null,
            'maxHeight' => null,
        ];
        $row1bis = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'organizationName' => 'Autorité 1',
            'description' => 'Description 1',
            'startDate' => $startDate1,
            'endDate' => $endDate1,
            'roadType' => $location1bis->roadType,
            'roadName' => $location1bis->roadName,
            'roadNumber' => $location1bis->roadNumber,
            'geometry' => $location1bis->geometry,
            'maxSpeed' => null,
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'restrictedVehicleTypes' => [],
            'exemptedVehicleTypes' => null,
            'heavyweightMaxWeight' => null,
            'maxWidth' => null,
            'maxLength' => null,
            'maxHeight' => null,
            'restrictedCritairTypes' => null,
        ];
        $row2 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'organizationName' => 'Autorité 2',
            'description' => 'Description 2',
            'startDate' => $startDate2,
            'endDate' => null,
            'roadType' => $location2->roadType,
            'roadName' => $location2->roadName,
            'roadNumber' => $location2->roadNumber,
            'geometry' => $location2->geometry,
            'maxSpeed' => null,
            'type' => MeasureTypeEnum::NO_ENTRY->value,
            'restrictedVehicleTypes' => ['heavyGoodsVehicle', 'dimensions'],
            'exemptedVehicleTypes' => ['commercial'],
            'heavyweightMaxWeight' => 3.5,
            'maxWidth' => 2,
            'maxLength' => 12,
            'maxHeight' => 2.4,
            'restrictedCritairTypes' => null,
        ];
        $row3 = [
            'uuid' => '12410fb8-a2b9-4449-a7d5-a4f409807f99',
            'organizationName' => 'Autorité 3',
            'description' => 'Description 3',
            'startDate' => $startDate3,
            'endDate' => $endDate3,
            'roadType' => $location3->roadType,
            'roadName' => $location3->roadName,
            'roadNumber' => $location3->roadNumber,
            'geometry' => $location3->geometry,
            'maxSpeed' => 50,
            'type' => MeasureTypeEnum::SPEED_LIMITATION->value,
            'restrictedVehicleTypes' => [VehicleTypeEnum::HAZARDOUS_MATERIALS->value],
            'exemptedVehicleTypes' => null,
            'heavyweightMaxWeight' => null,
            'maxWidth' => null,
            'maxLength' => null,
            'maxHeight' => null,
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
                                ),
                                new DatexVehicleConditionView(
                                    'dimensions',
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
