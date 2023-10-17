<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommandHandler;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandlerTest extends TestCase
{
    private $translator;
    private $commandBus;
    private $originalRegulationOrderRecord;
    private $originalRegulationOrder;

    public function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
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

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::exactly(2))
            ->method('getRestrictedTypes')
            ->willReturn([]);

        $period = $this->createMock(Period::class);
        $period
            ->expects(self::once())
            ->method('getApplicableDays')
            ->willReturn([ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::SUNDAY->value]);
        $period
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $period
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn($endTime);
        $period
            ->expects(self::once())
            ->method('isIncludeHolidays')
            ->willReturn(true);
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
        $measure2 = $this->createMock(Measure::class);
        $measure2
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::ALTERNATE_ROAD->value);
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
            ->method('getVehicleSet')
            ->willReturn(null);

        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->originalRegulationOrder);

        $originalOrganization = $this->createMock(Organization::class);
        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($originalOrganization);

        $location1 = $this->createMock(Location::class);
        $location1
            ->expects(self::once())
            ->method('getAddress')
            ->willReturn('Route du Lac 44260 Savenay');
        $location1
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn('11');
        $location1
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn('15');
        $location1
            ->expects(self::exactly(2))
            ->method('getMeasures')
            ->willReturn([$measure1, $measure2]);

        $location2 = $this->createMock(Location::class);
        $location2
            ->expects(self::once())
            ->method('getAddress')
            ->willReturn('Route du Grand Brossais 44260 Savenay');
        $location2
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn(null);
        $location2
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn(null);
        $location2
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([]);

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
            ->method('getStartDate')
            ->willReturn($startDate);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location1, $location2]);

        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('regulation.identifier.copy', [
                '%identifier%' => 'F01/2023',
            ])
            ->willReturn('F01/2023 (copie)');

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = 'F01/2023 (copie)';
        $generalInfoCommand->description = 'Description';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $generalInfoCommand->startDate = $startDate;
        $generalInfoCommand->endDate = $endDate;
        $generalInfoCommand->organization = $originalOrganization;

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->allVehicles = true;

        $periodCommand1 = new SavePeriodCommand();
        $periodCommand1->applicableDays = [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::SUNDAY->value];
        $periodCommand1->startTime = $startTime;
        $periodCommand1->endTime = $endTime;
        $periodCommand1->includeHolidays = true;

        $measureCommand1 = new SaveMeasureCommand();
        $measureCommand1->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand1->createdAt = $startDate;
        $measureCommand1->periods = [
            $periodCommand1,
        ];
        $measureCommand1->vehicleSet = $vehicleSetCommand;

        $measureCommand2 = new SaveMeasureCommand();
        $measureCommand2->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $measureCommand2->createdAt = $startDate;

        $locationCommand1 = new SaveRegulationLocationCommand($duplicatedRegulationOrderRecord);
        $locationCommand1->address = 'Route du Lac 44260 Savenay';
        $locationCommand1->fromHouseNumber = '11';
        $locationCommand1->toHouseNumber = '15';
        $locationCommand1->measures = [
            $measureCommand1,
            $measureCommand2,
        ];

        $locationCommand2 = new SaveRegulationLocationCommand($duplicatedRegulationOrderRecord);
        $locationCommand2->address = 'Route du Grand Brossais 44260 Savenay';
        $locationCommand2->fromHouseNumber = null;
        $locationCommand2->toHouseNumber = null;

        $this->commandBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->withConsecutive([$generalInfoCommand], [$locationCommand1], [$locationCommand2])
            ->willReturnOnConsecutiveCalls($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->translator,
            $this->commandBus,
        );

        $command = new DuplicateRegulationCommand($this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }
}
