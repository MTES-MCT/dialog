<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommandHandler;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Repository\ZoneRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveZoneCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $zoneRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->zoneRepository = $this->createMock(ZoneRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $createdZone = $this->createMock(Zone::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('setZone')
            ->with($createdZone);

        $this->zoneRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Zone(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $location,
                        label: 'Centre-ville',
                        geometry: '<polygon>',
                    ),
                ),
            )->willReturn($createdZone);

        $handler = new SaveZoneCommandHandler(
            $this->idFactory,
            $this->zoneRepository,
        );

        $command = new SaveZoneCommand();
        $command->location = $location;
        $command->roadType = RoadTypeEnum::ZONE->value;
        $command->label = 'Centre-ville';
        $command->geometry = '<polygon>';

        $result = $handler($command);

        $this->assertSame($createdZone, $result);
    }

    public function testUpdate(): void
    {
        $location = $this->createMock(Location::class);

        $zone = $this->createMock(Zone::class);
        $zone
            ->expects(self::once())
            ->method('getLocation')
            ->willReturn($location);
        $zone
            ->expects(self::once())
            ->method('update')
            ->with('Quartier de la gare', '<polygon-new>');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->zoneRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveZoneCommandHandler(
            $this->idFactory,
            $this->zoneRepository,
        );

        $command = new SaveZoneCommand($zone);
        $command->label = 'Quartier de la gare';
        $command->geometry = '<polygon-new>';

        $this->assertSame($zone, $handler($command));
    }
}
