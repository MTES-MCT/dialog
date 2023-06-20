<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommandHandler;
use App\Domain\Condition\VehicleSet;
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
                        restrictedTypes: ['critair5'],
                        otherRestrictedTypeText: null,
                        exemptedTypes: ['ambulance'],
                        otherExemptedTypeText: null,
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
        $command->restrictedTypes = ['critair5'];
        $command->exemptedTypes = ['ambulance'];

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
                ['heavyGoodsVehicle', 'other'],
                'Matières dangereuses',
                ['bus'],
                null,
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
        $command->exemptedTypes = ['bus'];
        $command->otherExemptedTypeText = null;

        $result = $handler($command);

        $this->assertSame($vehicleSet, $result);
    }
}
