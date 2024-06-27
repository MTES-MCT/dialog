<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommandHandler;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveRawGeoJSONCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $rawGeoJSONRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->rawGeoJSONRepository = $this->createMock(RawGeoJSONRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $createdRawGeoJSON = $this->createMock(RawGeoJSON::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('setRawGeoJSON')
            ->with($createdRawGeoJSON);

        $this->rawGeoJSONRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new RawGeoJSON(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $location,
                        label: 'Evénement spécial',
                    ),
                ),
            )->willReturn($createdRawGeoJSON);

        $handler = new SaveRawGeoJSONCommandHandler(
            $this->idFactory,
            $this->rawGeoJSONRepository,
        );

        $command = new SaveRawGeoJSONCommand();
        $command->location = $location;
        $command->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $command->label = 'Evénement spécial';
        $command->geometry = '<geometry>';

        $result = $handler($command);

        $this->assertSame($createdRawGeoJSON, $result);
    }

    public function testUpdate(): void
    {
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);

        $rawGeoJSON
            ->expects(self::once())
            ->method('getLabel')
            ->willReturn('Evénement spécial');

        $rawGeoJSON
            ->expects(self::once())
            ->method('update')
            ->with(
                'Evénement très spécial',
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->rawGeoJSONRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRawGeoJSONCommandHandler(
            $this->idFactory,
            $this->rawGeoJSONRepository,
        );

        $command = new SaveRawGeoJSONCommand($rawGeoJSON);
        $command->label = 'Evénement très spécial';

        $this->assertSame($rawGeoJSON, $handler($command));
    }
}
