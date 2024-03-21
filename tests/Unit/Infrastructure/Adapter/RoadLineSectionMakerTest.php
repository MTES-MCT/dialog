<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\GeometryServiceInterface;
use App\Application\RoadLine;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\RoadLineSectionMaker;
use PHPUnit\Framework\TestCase;

final class RoadLineSectionMakerTest extends TestCase
{
    private $roadLine;
    private $geocoder;
    private $geometryService;
    private $roadLineSectionMaker;

    protected function setUp(): void
    {
        $this->roadLine = new RoadLine(
            'geometry',
            'id',
            'Rue du Test',
            '01010',
        );

        $this->geocoder = $this->createMock(GeocoderInterface::class);
        $this->geometryService = $this->createMock(GeometryServiceInterface::class);

        $this->roadLineSectionMaker = new RoadLineSectionMaker(
            $this->geocoder,
            $this->geometryService,
        );
    }

    public function testFullRoad(): void
    {
        $this->assertSame($this->roadLine->geometry, $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: null,
            toHouseNumber: null,
            fromRoadName: null,
            toRoadName: null,
        ));
    }

    public function testStartOnly(): void
    {
        $this->geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('20 Rue du Test', '01010')
            ->willReturn(Coordinates::fromLonLat(3, 45));

        $this->geometryService
            ->expects(self::once())
            ->method('locatePointOnLine')
            ->with($this->roadLine->geometry, Coordinates::fromLonLat(3, 45))
            ->willReturn(0.7);

        $this->geometryService
            ->expects(self::once())
            ->method('getFirstPointOfLinestring')
            ->with($this->roadLine->geometry)
            ->willReturn(Coordinates::fromLonLat(2, 44));

        $this->geocoder
            ->expects(self::once())
            ->method('findHouseNumberOnRoad')
            ->with($this->roadLine->id, Coordinates::fromLonLat(2, 44))
            ->willReturn('1');

        $this->geometryService
            ->expects(self::once())
            ->method('clipLine')
            ->with($this->roadLine->geometry, 0.7, 1)
            ->willReturn('section');

        $this->assertSame('section', $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: '20',
            toHouseNumber: null,
            fromRoadName: null,
            toRoadName: null,
        ));
    }

    public function testEndOnly(): void
    {
        $this->geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('20 Rue du Test', '01010')
            ->willReturn(Coordinates::fromLonLat(3, 45));

        $this->geometryService
            ->expects(self::once())
            ->method('locatePointOnLine')
            ->with($this->roadLine->geometry, Coordinates::fromLonLat(3, 45))
            ->willReturn(0.7);

        $this->geometryService
            ->expects(self::once())
            ->method('getFirstPointOfLinestring')
            ->with($this->roadLine->geometry)
            ->willReturn(Coordinates::fromLonLat(2, 44));

        $this->geocoder
            ->expects(self::once())
            ->method('findHouseNumberOnRoad')
            ->with($this->roadLine->id, Coordinates::fromLonLat(2, 44))
            ->willReturn('1');

        $this->geometryService
            ->expects(self::once())
            ->method('clipLine')
            ->with($this->roadLine->geometry, 0, 0.7)
            ->willReturn('section');

        $this->assertSame('section', $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: null,
            toHouseNumber: '20',
            fromRoadName: null,
            toRoadName: null,
        ));
    }

    public function testBothEnds(): void
    {
        $this->geocoder
            ->expects(self::exactly(2))
            ->method('computeJunctionCoordinates')
            ->withConsecutive(
                ['Rue du Test', 'Rue du Début', '01010'],
                ['Rue du Test', 'Rue de la Fin', '01010'],
            )
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(3, 45),
                Coordinates::fromLonLat(2, 43),
            );

        $this->geometryService
            ->expects(self::exactly(2))
            ->method('locatePointOnLine')
            ->withConsecutive(
                [$this->roadLine->geometry, Coordinates::fromLonLat(3, 45)],
                [$this->roadLine->geometry, Coordinates::fromLonLat(2, 43)],
            )
            ->willReturnOnConsecutiveCalls(0.4, 0.7);

        $this->geometryService
            ->expects(self::never())
            ->method('getFirstPointOfLinestring');

        $this->geocoder
            ->expects(self::never())
            ->method('findHouseNumberOnRoad');

        $this->geometryService
            ->expects(self::once())
            ->method('clipLine')
            ->with($this->roadLine->geometry, 0.4, 0.7)
            ->willReturn('section');

        $this->assertSame('section', $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: null,
            toHouseNumber: null,
            fromRoadName: 'Rue du Début',
            toRoadName: 'Rue de la Fin',
        ));
    }

    public function testBothEndsMisaligned(): void
    {
        $this->geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->withConsecutive(
                ['3 Rue du Test', '01010'],
                ['20 Rue du Test', '01010'],
            )
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(3, 45),
                Coordinates::fromLonLat(2, 43),
            );

        $this->geometryService
            ->expects(self::exactly(2))
            ->method('locatePointOnLine')
            ->withConsecutive(
                [$this->roadLine->geometry, Coordinates::fromLonLat(3, 45)],
                [$this->roadLine->geometry, Coordinates::fromLonLat(2, 43)],
            )
            ->willReturnOnConsecutiveCalls(0.7, 0.4); // Simulate 'from' junction being after 'to' junction on the road line.

        $this->geometryService
            ->expects(self::never())
            ->method('getFirstPointOfLinestring');

        $this->geocoder
            ->expects(self::never())
            ->method('findHouseNumberOnRoad');

        $this->geometryService
            ->expects(self::once())
            ->method('clipLine')
            ->with($this->roadLine->geometry, 0.4, 0.7) // Values were put in correct order
            ->willReturn('section');

        $this->assertSame('section', $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: '3',
            toHouseNumber: '20',
            fromRoadName: null,
            toRoadName: null,
        ));
    }

    public function testStartOnlyMisaligned(): void
    {
        $this->geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('20 Rue du Test', '01010')
            ->willReturn(Coordinates::fromLonLat(3, 45));

        $this->geometryService
            ->expects(self::once())
            ->method('locatePointOnLine')
            ->with($this->roadLine->geometry, Coordinates::fromLonLat(3, 45))
            ->willReturn(0.5);

        $this->geometryService
            ->expects(self::once())
            ->method('getFirstPointOfLinestring')
            ->with($this->roadLine->geometry)
            ->willReturn(Coordinates::fromLonLat(2, 44));

        $this->geocoder
            ->expects(self::once())
            ->method('findHouseNumberOnRoad')
            ->with($this->roadLine->id, Coordinates::fromLonLat(2, 44))
            ->willReturn('40'); // House numbers go in reverse direction to road line points!

        $this->geometryService
            ->expects(self::once())
            ->method('clipLine')
            ->with($this->roadLine->geometry, 0, 0.5) // Instead of 0.5, 1 (which would wrongly target house numbers 20 to 1st)
            ->willReturn('section');

        $this->assertSame('section', $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: '20',
            toHouseNumber: null,
            fromRoadName: null,
            toRoadName: null,
        ));
    }

    public function testInvalidHouseNumber(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessage('Invalid house number');

        // In practice, 'abc Rue du Test' would not be accepted by the geocoder,
        // but we still need to test theoretical case where it would.

        $this->geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('abc Rue du Test', '01010')
            ->willReturn(Coordinates::fromLonLat(3, 45));

        $this->geometryService
            ->expects(self::once())
            ->method('locatePointOnLine')
            ->with($this->roadLine->geometry, Coordinates::fromLonLat(3, 45))
            ->willReturn(0.7);

        $this->geometryService
            ->expects(self::once())
            ->method('getFirstPointOfLinestring')
            ->with($this->roadLine->geometry)
            ->willReturn(Coordinates::fromLonLat(2, 44));

        $this->geocoder
            ->expects(self::once())
            ->method('findHouseNumberOnRoad')
            ->with($this->roadLine->id, Coordinates::fromLonLat(2, 44))
            ->willReturn('1');

        $this->geometryService
            ->expects(self::never())
            ->method('clipLine');

        $this->roadLineSectionMaker->computeRoadLineSection(
            $this->roadLine,
            fromCoords: null,
            toCoords: null,
            fromHouseNumber: 'abc',
            toHouseNumber: null,
            fromRoadName: null,
            toRoadName: null,
        );
    }
}
