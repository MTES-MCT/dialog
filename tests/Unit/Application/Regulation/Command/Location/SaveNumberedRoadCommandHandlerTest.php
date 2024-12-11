<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommandHandler;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveNumberedRoadCommandHandlerTest extends TestCase
{
    private ?string $administrator;
    private ?string $roadNumber;
    private string $geometry;
    private string $fromPointNumber;
    private string $fromSide;
    private int $fromAbscissa;
    private string $toPointNumber;
    private string $toSide;
    private int $toAbscissa;
    private string $direction;

    private MockObject $idFactory;
    private MockObject $numberedRoadRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->numberedRoadRepository = $this->createMock(NumberedRoadRepositoryInterface::class);

        $this->administrator = 'Département de Loire-Atlantique';
        $this->roadNumber = 'D12';
        $this->fromPointNumber = '1';
        $this->fromSide = 'U';
        $this->fromAbscissa = 0;
        $this->toPointNumber = '5';
        $this->toSide = 'U';
        $this->toAbscissa = 100;
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
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $location = $this->createMock(Location::class);
        $createdNumberedRoad = $this->createMock(NumberedRoad::class);

        $location
            ->expects(self::once())
            ->method('setNumberedRoad')
            ->with($createdNumberedRoad);

        $this->numberedRoadRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NumberedRoad(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        location: $location,
                        direction: $this->direction,
                        administrator: $this->administrator,
                        roadNumber: $this->roadNumber,
                        fromPointNumber: $this->fromPointNumber,
                        fromSide: $this->fromSide,
                        fromAbscissa: $this->fromAbscissa,
                        toPointNumber: $this->toPointNumber,
                        toSide: $this->toSide,
                        toAbscissa: $this->toAbscissa,
                    ),
                ),
            )
            ->willReturn($createdNumberedRoad);

        $handler = new SaveNumberedRoadCommandHandler(
            $this->idFactory,
            $this->numberedRoadRepository,
        );

        $command = new SaveNumberedRoadCommand();
        $command->location = $location;
        $command->direction = $this->direction;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;

        $result = $handler($command);

        $this->assertSame($createdNumberedRoad, $result);
    }

    public function testUpdate(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->direction,
                $this->administrator,
                $this->roadNumber,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->numberedRoadRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveNumberedRoadCommandHandler(
            $this->idFactory,
            $this->numberedRoadRepository,
        );

        $command = new SaveNumberedRoadCommand($numberedRoad);
        $command->direction = $this->direction;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;
        $this->assertSame($numberedRoad, $handler($command));
    }
}
