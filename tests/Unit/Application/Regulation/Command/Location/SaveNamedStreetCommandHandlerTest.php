<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommandHandler;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveNamedStreetCommandHandlerTest extends TestCase
{
    private string $cityCode;
    private string $cityLabel;
    private string $roadName;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;
    private string $direction;

    private $idFactory;
    private $namedStreetRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->namedStreetRepository = $this->createMock(NamedStreetRepositoryInterface::class);

        $this->cityCode = '44195';
        $this->cityLabel = 'Savenay';
        $this->roadName = 'Route du Grand Brossais';
        $this->fromHouseNumber = '15';
        $this->toHouseNumber = '37bis';
        $this->direction = DirectionEnum::BOTH->value;
        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $createdNamedStreet = $this->createMock(NamedStreet::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('setNamedStreet')
            ->with($createdNamedStreet);

        $this->namedStreetRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NamedStreet(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $location,
                        direction: $this->direction,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: $this->fromHouseNumber,
                        toHouseNumber: $this->toHouseNumber,
                    ),
                ),
            )->willReturn($createdNamedStreet);

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->namedStreetRepository,
        );

        $command = new SaveNamedStreetCommand();
        $command->location = $location;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->direction = DirectionEnum::BOTH->value;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $result = $handler($command);

        $this->assertSame($createdNamedStreet, $result);
    }

    public function testUpdate(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);

        $namedStreet
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn('15');

        $namedStreet
            ->expects(self::once())
            ->method('getFromRoadName')
            ->willReturn(null);

        $namedStreet
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn('37bis');

        $namedStreet
            ->expects(self::once())
            ->method('getToRoadName')
            ->willReturn(null);

        $namedStreet
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->direction,
                $this->cityCode,
                $this->cityLabel,
                $this->roadName,
                $this->fromHouseNumber,
                null,
                $this->toHouseNumber,
                null,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->namedStreetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->namedStreetRepository,
        );

        $command = new SaveNamedStreetCommand($namedStreet);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->direction = $this->direction;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->fromRoadName = null;
        $command->toHouseNumber = $this->toHouseNumber;
        $command->toRoadName = null;

        $this->assertSame($namedStreet, $handler($command));
    }
}
