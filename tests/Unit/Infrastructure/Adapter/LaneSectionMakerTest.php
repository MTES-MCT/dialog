<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\GeocoderInterface;
use App\Application\IntersectionGeocoderInterface;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Infrastructure\Adapter\LaneSectionMaker;
use PHPUnit\Framework\TestCase;

final class LaneSectionMakerTest extends TestCase
{
    private $fullLaneGeometry;
    private $roadName;
    private $cityCode;
    private $fromCoords;
    private $toCoords;
    private $geocoder;
    private string $direction;
    private $intersectionGeocoder;
    private $lineSectionMaker;
    private $laneSectionMaker;

    protected function setUp(): void
    {
        $this->fullLaneGeometry = 'geometry';
        $this->roadName = 'Rue du Test';
        $this->cityCode = '01010';
        $this->fromCoords = Coordinates::fromLonLat(1, 41);
        $this->toCoords = Coordinates::fromLonLat(9, 49);
        $this->direction = DirectionEnum::BOTH->value;

        $this->geocoder = $this->createMock(GeocoderInterface::class);
        $this->intersectionGeocoder = $this->createMock(IntersectionGeocoderInterface::class);
        $this->lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);

        $this->laneSectionMaker = new LaneSectionMaker(
            $this->geocoder,
            $this->intersectionGeocoder,
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

        $this->intersectionGeocoder
            ->expects(self::once())
            ->method('computeIntersection')
            ->with($this->roadName, 'Rue de la Fin', $this->cityCode)
            ->willReturn($this->toCoords);

        $this->lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($this->fullLaneGeometry, $this->fromCoords, $this->toCoords)
            ->willReturn('section');

        $this->assertSame('section', $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadName,
            $this->cityCode,
            $this->direction,
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

        $this->intersectionGeocoder
            ->expects(self::never())
            ->method('computeIntersection');

        $this->lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($this->fullLaneGeometry, $this->fromCoords, $this->toCoords)
            ->willReturn('section');

        $this->assertSame('section', $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadName,
            $this->cityCode,
            $this->direction,
            fromCoords: $this->fromCoords,
            fromHouseNumber: null,
            fromRoadName: null,
            toCoords: $this->toCoords,
            toHouseNumber: null,
            toRoadName: null,
        ));
    }
}
