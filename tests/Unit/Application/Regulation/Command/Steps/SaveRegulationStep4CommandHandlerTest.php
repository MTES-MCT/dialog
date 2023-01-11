<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep4Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep4CommandHandler;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\VehicleCharacteristicsRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep4CommandHandlerTest extends TestCase
{
    private $regulationCondition;
    private $regulationOrder;
    private $regulationOrderRecord;

    protected function setUp(): void
    {
        $this->regulationCondition = $this->createMock(RegulationCondition::class);
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->regulationOrder
            ->expects(self::once())
            ->method('getRegulationCondition')
            ->willReturn($this->regulationCondition);

        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('updateLastFilledStep')
            ->with(4);
    }

    public function testCreate(): void
    {
        $maxWeight = 3.5;
        $maxHeight = 2.80;
        $maxWidth = 2;
        $maxLength = 12.5;

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('cc8e93af-fde9-414d-9184-65e6fd3a6cad');

        $vehicleCharacteristicsRepository = $this->createMock(VehicleCharacteristicsRepositoryInterface::class);
        $vehicleCharacteristicsRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                new VehicleCharacteristics(
                    uuid: 'cc8e93af-fde9-414d-9184-65e6fd3a6cad',
                    regulationCondition: $this->regulationCondition,
                    maxWeight: $maxWeight,
                    maxHeight: $maxHeight,
                    maxWidth: $maxWidth,
                    maxLength: $maxLength,
                )
            );

        $handler = new SaveRegulationStep4CommandHandler(
            $idFactory,
            $vehicleCharacteristicsRepository,
        );

        $command = new SaveRegulationStep4Command($this->regulationOrderRecord);
        $command->maxWeight = $maxWeight;
        $command->maxHeight = $maxHeight;
        $command->maxWidth = $maxWidth;
        $command->maxLength = $maxLength;

        $this->assertEmpty($handler($command));
    }

    public function testUpdate(): void
    {
        $maxWeight = 3.5;
        $maxHeight = 2.80;
        $maxWidth = 2;
        $maxLength = 12.5;

        $vehicleCharacteristics = $this->createMock(VehicleCharacteristics::class);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('update')
            ->with($maxWeight, $maxHeight, $maxWidth, $maxLength);

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleCharacteristicsRepository = $this->createMock(VehicleCharacteristicsRepositoryInterface::class);
        $vehicleCharacteristicsRepository
            ->expects(self::never())
            ->method('save');

        $handler = new SaveRegulationStep4CommandHandler(
            $idFactory,
            $vehicleCharacteristicsRepository,
        );

        $command = new SaveRegulationStep4Command($this->regulationOrderRecord, $vehicleCharacteristics);
        $command->maxWeight = $maxWeight;
        $command->maxHeight = $maxHeight;
        $command->maxWidth = $maxWidth;
        $command->maxLength = $maxLength;

        $this->assertEmpty($handler($command));
    }

    public function testEmptyCommand(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleCharacteristicsRepository = $this->createMock(VehicleCharacteristicsRepositoryInterface::class);
        $vehicleCharacteristicsRepository
            ->expects(self::never())
            ->method('save');

        $handler = new SaveRegulationStep4CommandHandler(
            $idFactory,
            $vehicleCharacteristicsRepository,
        );

        $command = new SaveRegulationStep4Command($this->regulationOrderRecord);

        $this->assertEmpty($handler($command));
    }
}
