<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommandHandler;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\Regulation\Query\GetDuplicateIdentifierQuery;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Condition\VehicleSet;
use App\Domain\Organization\VisaModel\VisaModel;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class DuplicateRegulationCommandHandlerTest extends TestCase
{
    private $queryBus;
    private $commandBus;
    private $originalRegulationOrderRecord;
    private $originalRegulationOrder;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->originalRegulationOrder = $this->createMock(RegulationOrder::class);
        $this->originalRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    public function testRegulationFullyDuplicated(): void
    {
        $startDate = new \DateTimeImmutable('2023-03-13');
        $endDate = new \DateTimeImmutable('2023-03-16');

        $startTime = new \DateTimeImmutable('2023-03-13 08:00:00');
        $endTime = new \DateTimeImmutable('2023-03-13 20:00:00');

        $timeSlotStartTime = new \DateTimeImmutable('2023-03-13 08:00:00');
        $timeSlotEndTime = new \DateTimeImmutable('2023-03-13 20:00:00');

        $location1 = $this->createMock(Location::class);
        $location2 = $this->createMock(Location::class);
        $location3 = $this->createMock(Location::class);

        $timeSlot = $this->createMock(TimeSlot::class);
        $timeSlot
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn($timeSlotStartTime);
        $timeSlot
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn($timeSlotEndTime);

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getRestrictedTypes')
            ->willReturn([]);

        $dailyRange = $this->createMock(DailyRange::class);
        $dailyRange
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn([ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::SUNDAY->value]);

        $period = $this->createMock(Period::class);
        $period
            ->expects(self::once())
            ->method('getRecurrenceType')
            ->willReturn(PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value);
        $period
            ->expects(self::exactly(2))
            ->method('getStartDateTime')
            ->willReturn($startTime);
        $period
            ->expects(self::exactly(2))
            ->method('getEndDateTime')
            ->willReturn($endTime);
        $period
            ->expects(self::once())
            ->method('getDailyRange')
            ->willReturn($dailyRange);
        $period
            ->expects(self::exactly(2))
            ->method('getTimeSlots')
            ->willReturn([$timeSlot]);

        $measure1 = $this->createMock(Measure::class);
        $measure1
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);
        $measure1
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn($startDate);
        $measure1
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period]);
        $measure1
            ->expects(self::exactly(2))
            ->method('getVehicleSet')
            ->willReturn($vehicleSet);
        $measure1
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location1, $location2, $location3]);

        $measure2 = $this->createMock(Measure::class);
        $measure2
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::NO_ENTRY->value);
        $measure2
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn($startDate);
        $measure2
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([]);
        $measure2
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([]);
        $measure2
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn(null);

        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->originalRegulationOrder);
        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('isPermanent')
            ->willReturn(false);

        $originalOrganization = $this->createMock(Organization::class);
        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($originalOrganization);

        $namedStreet1 = $this->createMock(NamedStreet::class);
        $namedStreet1
            ->expects(self::once())
            ->method('getCityCode')
            ->willReturn('44195');
        $location1
            ->expects(self::once())
            ->method('getNamedStreet')
            ->willReturn($namedStreet1);
        $namedStreet1
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn('Savenay');
        $location1
            ->expects(self::exactly(2))
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::LANE->value);
        $location1
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn('streetGeometry');
        $namedStreet1
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Route du Lac');
        $namedStreet1
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn('11');
        $namedStreet1
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn('15');

        $numberedRoad1 = $this->createMock(NumberedRoad::class);
        $location2
            ->expects(self::once())
            ->method('getNumberedRoad')
            ->willReturn($numberedRoad1);
        $numberedRoad1
            ->expects(self::once())
            ->method('getAdministrator')
            ->willReturn('Ardèche');
        $numberedRoad1
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn('D110');
        $location2
            ->expects(self::exactly(2))
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::DEPARTMENTAL_ROAD->value);
        $numberedRoad1
            ->expects(self::once())
            ->method('getFromPointNumber')
            ->willReturn('1');
        $numberedRoad1
            ->expects(self::once())
            ->method('getToPointNumber')
            ->willReturn('3');
        $location2
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn('roadGeometry');

        $visaModel = $this->createMock(VisaModel::class);
        $visaModel
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('P67f7f275-51b2-4f7f-914a-45168a28d4c2');

        $rawGeoJSON1 = $this->createMock(RawGeoJSON::class);
        $location3
            ->expects(self::exactly(2))
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::RAW_GEOJSON->value);
        $location3
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn('rawGeometry');
        $location3
            ->expects(self::once())
            ->method('getRawGeoJSON')
            ->willReturn($rawGeoJSON1);
        $rawGeoJSON1
            ->expects(self::once())
            ->method('getLabel')
            ->willReturn('Données');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2023');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getCategory')
            ->willReturn(RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getOtherCategoryText')
            ->willReturn(null);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getAdditionalVisas')
            ->willReturn(null);
        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getVisaModel')
            ->willReturn($visaModel);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getAdditionalReasons')
            ->willReturn(['Motif 1']);

        $this->originalRegulationOrder
            ->expects(self::exactly(2))
            ->method('getMeasures')
            ->willReturn([$measure1, $measure2]);

        $duplicatedRegulationOrder = $this->createMock(RegulationOrder::class);
        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $duplicatedRegulationOrderRecord
            ->expects(self::exactly(2))
            ->method('getRegulationOrder')
            ->willReturn($duplicatedRegulationOrder);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetDuplicateIdentifierQuery('F01/2023', $originalOrganization))
            ->willReturn('F01/2023-1');

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = 'F01/2023-1';
        $generalInfoCommand->description = 'Description';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $generalInfoCommand->organization = $originalOrganization;
        $generalInfoCommand->additionalVisas = [];
        $generalInfoCommand->additionalReasons = ['Motif 1'];
        $generalInfoCommand->visaModelUuid = 'P67f7f275-51b2-4f7f-914a-45168a28d4c2';

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;

        $timeSlotCommand = new SaveTimeSlotCommand();
        $timeSlotCommand->startTime = $timeSlotStartTime;
        $timeSlotCommand->endTime = $timeSlotEndTime;

        $dailyRangeCommand = new SaveDailyRangeCommand();
        $dailyRangeCommand->applicableDays = [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::SUNDAY->value];

        $periodCommand1 = new SavePeriodCommand();
        $periodCommand1->startTime = $startTime;
        $periodCommand1->endTime = $endTime;
        $periodCommand1->startDate = $startTime;
        $periodCommand1->endDate = $endTime;
        $periodCommand1->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $periodCommand1->dailyRange = $dailyRangeCommand;
        $periodCommand1->isPermanent = false;
        $periodCommand1->timeSlots = [
            $timeSlotCommand,
        ];

        $locationCommand1 = new SaveLocationCommand();
        $locationCommand1->roadType = RoadTypeEnum::LANE->value;
        $locationCommand1->namedStreet = new SaveNamedStreetCommand();
        $locationCommand1->namedStreet->cityCode = '44195';
        $locationCommand1->namedStreet->cityLabel = 'Savenay';
        $locationCommand1->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand1->namedStreet->roadName = 'Route du Lac';
        $locationCommand1->namedStreet->fromHouseNumber = '11';
        $locationCommand1->namedStreet->toHouseNumber = '15';
        $locationCommand1->namedStreet->geometry = 'streetGeometry';

        $locationCommand2 = new SaveLocationCommand();
        $locationCommand2->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $locationCommand2->numberedRoad = new SaveNumberedRoadCommand();
        $locationCommand2->numberedRoad->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $locationCommand2->numberedRoad->administrator = 'Ardèche';
        $locationCommand2->numberedRoad->roadNumber = 'D110';
        $locationCommand2->numberedRoad->fromPointNumber = '1';
        $locationCommand2->numberedRoad->toPointNumber = '3';
        $locationCommand2->numberedRoad->geometry = 'roadGeometry';

        $locationCommand3 = new SaveLocationCommand();
        $locationCommand3->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $locationCommand3->rawGeoJSON = new SaveRawGeoJSONCommand();
        $locationCommand3->rawGeoJSON->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $locationCommand3->rawGeoJSON->label = 'Données';
        $locationCommand3->rawGeoJSON->geometry = 'rawGeometry';

        $measureCommand1 = new SaveMeasureCommand($duplicatedRegulationOrder);
        $measureCommand1->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand1->createdAt = $startDate;
        $measureCommand1->periods = [
            $periodCommand1,
        ];
        $measureCommand1->locations = [
            $locationCommand1,
            $locationCommand2,
            $locationCommand3,
        ];
        $measureCommand1->vehicleSet = $vehicleSetCommand;

        $measureCommand2 = new SaveMeasureCommand($duplicatedRegulationOrder);
        $measureCommand2->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand2->createdAt = $startDate;

        $this->commandBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->withConsecutive([$generalInfoCommand], [$measureCommand1], [$measureCommand2])
            ->willReturnOnConsecutiveCalls($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->queryBus,
            $this->commandBus,
        );

        $command = new DuplicateRegulationCommand($this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }
}
