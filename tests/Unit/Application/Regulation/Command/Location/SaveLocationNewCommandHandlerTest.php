<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveLocationNewCommand;
use App\Application\Regulation\Command\Location\SaveLocationNewCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveLocationNewCommandHandlerTest extends TestCase
{
    private string $roadType;
    private ?string $administrator;
    private ?string $roadNumber;
    private string $cityCode;
    private string $cityLabel;
    private string $roadName;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;
    private MockObject $idFactory;
    private MockObject $locationNewRepository;
    private MockObject $geocoder;
    private MockObject $roadGeocoder;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->locationNewRepository = $this->createMock(LocationNewRepositoryInterface::class);
        $this->geocoder = $this->createMock(GeocoderInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);

        $this->roadType = 'lane';
        $this->administrator = null;
        $this->roadNumber = null;
        $this->cityCode = '44195';
        $this->cityLabel = 'Savenay';
        $this->roadName = 'Route du Grand Brossais';
        $this->fromHouseNumber = '15';
        $this->toHouseNumber = '37bis';
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

        $this->geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            );

        $createdLocationNew = $this->createMock(LocationNew::class);
        $measure = $this->createMock(Measure::class);

        $this->locationNewRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new LocationNew(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: $this->roadType,
                        administrator: $this->administrator,
                        roadNumber: $this->roadNumber,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: $this->fromHouseNumber,
                        toHouseNumber: $this->toHouseNumber,
                        geometry: $this->geometry,
                    ),
                ),
            )
            ->willReturn($createdLocationNew);

        $handler = new SaveLocationNewCommandHandler(
            $this->idFactory,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveLocationNewCommand();
        $command->measure = $measure;
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;
        $command->geometry = $this->geometry;

        $result = $handler($command);

        $this->assertSame($createdLocationNew, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            );

        $measure = $this->createMock(Measure::class);
        $locationNew = $this->createMock(LocationNew::class);
        $locationNew
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->administrator,
                $this->roadNumber,
                $this->cityCode,
                $this->cityLabel,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                $this->geometry,
            );

        $handler = new SaveLocationNewCommandHandler(
            $this->idFactory,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveLocationNewCommand($locationNew);
        $command->measure = $measure;
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;
        $command->geometry = $this->geometry;

        $result = $handler($command);

        $this->assertSame($locationNew, $result);
    }
}
