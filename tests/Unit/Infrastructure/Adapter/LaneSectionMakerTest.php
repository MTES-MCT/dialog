<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\GeocoderInterface;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Adapter\LaneSectionMaker;
use PHPUnit\Framework\TestCase;

final class LaneSectionMakerTest extends TestCase
{
    private $fullLaneGeometry;
    private $roadType;
    private $roadName;
    private $cityCode;
    private $fromCoords;
    private $toCoords;
    private $geocoder;
    private $lineSectionMaker;
    private $laneSectionMaker;

    protected function setUp(): void
    {
        $this->fullLaneGeometry = 'geometry';
        $this->roadType = RoadTypeEnum::LANE;
        $this->roadName = 'Rue du Test';
        $this->cityCode = '01010';
        $this->fromCoords = Coordinates::fromLonLat(1, 41);
        $this->toCoords = Coordinates::fromLonLat(9, 49);

        $this->geocoder = $this->createMock(GeocoderInterface::class);
        $this->lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);

        $this->laneSectionMaker = new LaneSectionMaker(
            $this->geocoder,
            $this->lineSectionMaker,
        );
    }

    public function testComputeSection(): void
    {
        $this->geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('1 Rue du Test', $this->cityCode)
            ->willReturn($this->fromCoords);

        $this->geocoder
            ->expects(self::once())
            ->method('computeJunctionCoordinates')
            ->with($this->roadName, 'Rue de la Fin', $this->cityCode)
            ->willReturn($this->toCoords);

        $this->lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($this->roadType, $this->fullLaneGeometry, $this->fromCoords, $this->toCoords)
            ->willReturn('section');

        $this->assertSame('section', $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadName,
            $this->cityCode,
            fromCoords: null,
            fromHouseNumber: '1',
            fromRoadName: null,
            toCoords: null,
            toHouseNumber: null,
            toRoadName: 'Rue de la Fin',
        ));
    }

    public function testComputeSectionUsingCoords(): void
    {
        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $this->geocoder
            ->expects(self::never())
            ->method('computeJunctionCoordinates');

        $this->lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($this->roadType, $this->fullLaneGeometry, $this->fromCoords, $this->toCoords)
            ->willReturn('section');

        $this->assertSame('section', $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadName,
            $this->cityCode,
            fromCoords: $this->fromCoords,
            fromHouseNumber: null,
            fromRoadName: null,
            toCoords: $this->toCoords,
            toHouseNumber: null,
            toRoadName: null,
        ));
    }
}
