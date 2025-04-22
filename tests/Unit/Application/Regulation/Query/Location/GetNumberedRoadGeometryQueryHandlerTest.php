<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Query\Location\GetNumberedRoadGeometryQuery;
use App\Application\Regulation\Query\Location\GetNumberedRoadGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NumberedRoad;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetNumberedRoadGeometryQueryHandlerTest extends TestCase
{
    private string $roadType;
    private ?string $administrator;
    private ?string $roadNumber;
    private string $geometry;
    private ?string $fromDepartmentCode;
    private string $fromPointNumber;
    private string $fromSide;
    private int $fromAbscissa;
    private ?string $toDepartmentCode;
    private string $toPointNumber;
    private string $toSide;
    private int $toAbscissa;

    private MockObject $roadGeocoder;
    private MockObject $roadSectionMaker;

    public function setUp(): void
    {
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->roadSectionMaker = $this->createMock(RoadSectionMakerInterface::class);

        $this->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $this->administrator = 'Département de Loire-Atlantique';
        $this->roadNumber = 'D12';
        $this->fromDepartmentCode = null;
        $this->fromPointNumber = '1';
        $this->fromSide = 'U';
        $this->fromAbscissa = 0;
        $this->toDepartmentCode = null;
        $this->toPointNumber = '5';
        $this->toSide = 'U';
        $this->toAbscissa = 100;

        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
    }

    public function testGet(): void
    {
        $fullDepartmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->with($this->roadType, $this->administrator, $this->roadNumber)
            ->willReturn($fullDepartmentalRoadGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullDepartmentalRoadGeometry,
                $this->roadType,
                $this->administrator,
                $this->roadNumber,
                $this->fromDepartmentCode,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toDepartmentCode,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
            )
            ->willReturn('sectionGeometry');

        $handler = new GetNumberedRoadGeometryQueryHandler(
            $this->roadSectionMaker,
            $this->roadGeocoder,
        );

        $command = new SaveNumberedRoadCommand();
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromDepartmentCode = $this->fromDepartmentCode;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toDepartmentCode = $this->toDepartmentCode;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;

        $result = $handler(new GetNumberedRoadGeometryQuery($command));

        $this->assertSame('sectionGeometry', $result);
    }

    public function testGetWithGeometry(): void
    {
        $location = $this->createMock(Location::class);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoad');

        $this->roadSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $handler = new GetNumberedRoadGeometryQueryHandler(
            $this->roadSectionMaker,
            $this->roadGeocoder,
        );

        $command = new SaveNumberedRoadCommand();
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromDepartmentCode = $this->fromDepartmentCode;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toDepartmentCode = $this->toDepartmentCode;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;

        $result = $handler(new GetNumberedRoadGeometryQuery($command, $location, 'sectionGeometry'));

        $this->assertSame('sectionGeometry', $result);
    }

    public function testGetWithLocationToRecompute(): void
    {
        $location = $this->createMock(Location::class);
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn('D120'); // Changed

        $fullDepartmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->with($this->roadType, $this->administrator, $this->roadNumber)
            ->willReturn($fullDepartmentalRoadGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullDepartmentalRoadGeometry,
                $this->roadType,
                $this->administrator,
                $this->roadNumber,
                $this->fromDepartmentCode,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toDepartmentCode,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
            )
            ->willReturn('sectionGeometry');

        $handler = new GetNumberedRoadGeometryQueryHandler(
            $this->roadSectionMaker,
            $this->roadGeocoder,
        );

        $command = new SaveNumberedRoadCommand($numberedRoad);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromDepartmentCode = $this->fromDepartmentCode;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toDepartmentCode = $this->toDepartmentCode;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;

        $result = $handler(new GetNumberedRoadGeometryQuery($command, $location));

        $this->assertSame('sectionGeometry', $result);
    }

    public function testGetWithLocationToNotRecompute(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn('sectionGeometry');

        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromDepartmentCode')
            ->willReturn($this->fromDepartmentCode);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromPointNumber')
            ->willReturn($this->fromPointNumber);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromAbscissa')
            ->willReturn($this->fromAbscissa);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromSide')
            ->willReturn($this->fromSide);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToDepartmentCode')
            ->willReturn($this->toDepartmentCode);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToPointNumber')
            ->willReturn($this->toPointNumber);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToAbscissa')
            ->willReturn($this->toAbscissa);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToSide')
            ->willReturn($this->toSide);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getAdministrator')
            ->willReturn($this->administrator);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn($this->roadNumber);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoad');

        $this->roadSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $handler = new GetNumberedRoadGeometryQueryHandler(
            $this->roadSectionMaker,
            $this->roadGeocoder,
        );

        $command = new SaveNumberedRoadCommand($numberedRoad);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromDepartmentCode = $this->fromDepartmentCode;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toDepartmentCode = $this->toDepartmentCode;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;

        $result = $handler(new GetNumberedRoadGeometryQuery($command, $location));

        $this->assertSame('sectionGeometry', $result);
    }
}
