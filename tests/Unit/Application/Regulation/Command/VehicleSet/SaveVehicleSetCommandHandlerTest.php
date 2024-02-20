<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\VehicleSet;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommandHandler;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\VehicleSetRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveVehicleSetCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $vehicleSetRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->vehicleSetRepository = $this->createMock(VehicleSetRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdVehicleSet = $this->createMock(VehicleSet::class);
        $measure = $this->createMock(Measure::class);

        $this->vehicleSetRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new VehicleSet(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::CRITAIR->value],
                        otherRestrictedTypeText: null,
                        exemptedTypes: [VehicleTypeEnum::EMERGENCY_SERVICES->value],
                        otherExemptedTypeText: null,
                        heavyweightMaxWeight: 3.5,
                        critairTypes: [CritairEnum::CRITAIR_2->value, CritairEnum::CRITAIR_3->value],
                    ),
                ),
            )
            ->willReturn($createdVehicleSet);

        $handler = new SaveVehicleSetCommandHandler(
            $this->idFactory,
            $this->vehicleSetRepository,
        );

        $command = new SaveVehicleSetCommand();
        $command->measure = $measure;
        $command->allVehicles = false;
        $command->restrictedTypes = [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::CRITAIR->value];
        $command->exemptedTypes = [VehicleTypeEnum::EMERGENCY_SERVICES->value];
        $command->heavyweightMaxWeight = 3.5;
        $command->critairTypes = [CritairEnum::CRITAIR_2->value, CritairEnum::CRITAIR_3->value];

        $result = $handler($command);

        $this->assertSame($createdVehicleSet, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::once())
            ->method('update')
            ->with(
                ['heavyGoodsVehicle', 'dimensions', 'other'],
                'Matières dangereuses',
                ['commercial'],
                null,
                3.5,
                2,
                12,
                2.4,
                [],
            );

        $this->vehicleSetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveVehicleSetCommandHandler(
            $this->idFactory,
            $this->vehicleSetRepository,
        );

        $command = new SaveVehicleSetCommand($vehicleSet);
        $command->allVehicles = false;
        $command->restrictedTypes = ['heavyGoodsVehicle', 'dimensions', 'other'];
        $command->otherRestrictedTypeText = 'Matières dangereuses';
        $command->exemptedTypes = ['commercial'];
        $command->otherExemptedTypeText = null;
        $command->heavyweightMaxWeight = 3.5;
        $command->maxWidth = 2;
        $command->maxLength = 12;
        $command->maxHeight = 2.4;
        $command->critairTypes = [];

        $result = $handler($command);

        $this->assertSame($vehicleSet, $result);
    }

    public function testResetRestrictionsAllVehicles(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::once())
            ->method('update')
            ->with(
                [],
                null,
                ['other'],
                'Other exempted',
                null,
                null,
                null,
                null,
                [],
            );

        $this->vehicleSetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveVehicleSetCommandHandler(
            $this->idFactory,
            $this->vehicleSetRepository,
        );

        $command = new SaveVehicleSetCommand($vehicleSet);
        $command->allVehicles = true;
        $command->restrictedTypes = [VehicleTypeEnum::OTHER->value];
        $command->otherRestrictedTypeText = 'Other restriction';
        $command->exemptedTypes = [VehicleTypeEnum::OTHER->value];
        $command->otherExemptedTypeText = 'Other exempted';
        $command->heavyweightMaxWeight = 3.5;
        $command->maxWidth = 2;
        $command->maxLength = 12;
        $command->maxHeight = 2.4;

        $result = $handler($command);

        $this->assertSame($vehicleSet, $result);
    }

    public function testResetCharacteristicsIfVehicleTypeNotRestricted(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::once())
            ->method('update')
            ->with(
                ['other'],
                'Matières dangereuses',
                ['commercial'],
                null,
                null,
                null,
                null,
                null,
                [],
            );

        $this->vehicleSetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveVehicleSetCommandHandler(
            $this->idFactory,
            $this->vehicleSetRepository,
        );

        $command = new SaveVehicleSetCommand($vehicleSet);
        $command->allVehicles = false;
        $command->restrictedTypes = ['other']; // heavyGoodsVehicle and dimensions not included
        $command->otherRestrictedTypeText = 'Matières dangereuses';
        $command->exemptedTypes = ['commercial'];
        $command->otherExemptedTypeText = null;
        $command->heavyweightMaxWeight = 3.5;
        $command->maxWidth = 2;
        $command->maxLength = 12;
        $command->maxHeight = 2.4;

        $result = $handler($command);

        $this->assertSame($vehicleSet, $result);
    }

    public function testResetCharacteristicsIfZero(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::once())
            ->method('update')
            ->with(
                ['heavyGoodsVehicle', 'dimensions'],
                null,
                [],
                null,
                0.0,
                0.0,
                0.0,
                0.0,
                [],
            );

        $this->vehicleSetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveVehicleSetCommandHandler(
            $this->idFactory,
            $this->vehicleSetRepository,
        );

        $command = new SaveVehicleSetCommand($vehicleSet);
        $command->allVehicles = false;
        $command->restrictedTypes = ['heavyGoodsVehicle', 'dimensions'];
        $command->heavyweightMaxWeight = 0;
        $command->maxWidth = 0.0;
        $command->maxLength = -0;
        $command->maxHeight = -0.0;

        $result = $handler($command);

        $this->assertSame($vehicleSet, $result);
    }

    public function testResetCritair(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $vehicleSet = $this->createMock(VehicleSet::class);
        $vehicleSet
            ->expects(self::once())
            ->method('update')
            ->with(
                ['heavyGoodsVehicle', 'other'],
                'Matières dangereuses',
                ['commercial'],
                null,
                null,
                null,
                null,
                null,
                [],
            );

        $this->vehicleSetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveVehicleSetCommandHandler(
            $this->idFactory,
            $this->vehicleSetRepository,
        );

        $command = new SaveVehicleSetCommand($vehicleSet);
        $command->allVehicles = false;
        $command->restrictedTypes = ['heavyGoodsVehicle', 'other'];
        $command->otherRestrictedTypeText = 'Matières dangereuses';
        $command->exemptedTypes = ['commercial'];
        $command->otherExemptedTypeText = null;
        $command->critairTypes = [CritairEnum::CRITAIR_2->value];

        $result = $handler($command);

        $this->assertSame($vehicleSet, $result);
    }
}
