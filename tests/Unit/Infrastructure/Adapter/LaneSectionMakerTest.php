<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\IntersectionGeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\IntersectionGeocoderInterface;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\LaneSectionMaker;
use PHPUnit\Framework\TestCase;

final class LaneSectionMakerTest extends TestCase
{
    private $fullLaneGeometry;
    private $roadBanId;
    private $roadName;
    private $cityCode;
    private $fromCoords;
    private $toCoords;
    private $geocoder;
    private $intersectionGeocoder;
    private $lineSectionMaker;
    private $laneSectionMaker;

    protected function setUp(): void
    {
        $this->fullLaneGeometry = 'geometry';
        $this->roadBanId = '01010_1234';
        $this->roadName = 'Rue du Test';
        $this->cityCode = '01010';
        $this->fromCoords = Coordinates::fromLonLat(1, 41);
        $this->toCoords = Coordinates::fromLonLat(9, 49);

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
            ->with($this->roadBanId, '01010_5678')
            ->willReturn($this->toCoords);

        $this->lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($this->fullLaneGeometry, $this->fromCoords, $this->toCoords)
            ->willReturn('section');

        $this->assertSame('section', $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadBanId,
            $this->roadName,
            $this->cityCode,
            fromCoords: null,
            fromHouseNumber: '1',
            fromRoadBanId: null,
            toCoords: null,
            toHouseNumber: null,
            toRoadBanId: '01010_5678',
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
            $this->roadBanId,
            $this->roadName,
            $this->cityCode,
            fromCoords: $this->fromCoords,
            fromHouseNumber: null,
            fromRoadBanId: null,
            toCoords: $this->toCoords,
            toHouseNumber: null,
            toRoadBanId: null,
        ));
    }

    public function testComputeSectionIntersectionNotFound(): void
    {
        $this->expectException(IntersectionGeocodingFailureException::class);

        $this->intersectionGeocoder
            ->expects(self::once())
            ->method('computeIntersection')
            ->with($this->roadBanId, '01010_5678')
            ->willThrowException(new IntersectionGeocodingFailureException('no intersection found'));

        $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadBanId,
            $this->roadName,
            $this->cityCode,
            fromCoords: $this->fromCoords,
            fromHouseNumber: null,
            fromRoadBanId: null,
            toCoords: null,
            toHouseNumber: null,
            toRoadBanId: '01010_5678',
        );
    }
}
