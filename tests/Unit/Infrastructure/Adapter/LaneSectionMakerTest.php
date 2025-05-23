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
    private $roadBanId;
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
        $this->roadBanId = '01010_1234';
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
            $this->direction,
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
            $this->direction,
            fromCoords: $this->fromCoords,
            fromHouseNumber: null,
            fromRoadBanId: null,
            toCoords: $this->toCoords,
            toHouseNumber: null,
            toRoadBanId: null,
        ));
    }

    private function provideTestComputeSectionDirection(): array
    {
        $this->setUp();

        $fromCoords = $this->fromCoords;
        $toCoords = $this->toCoords;

        return [
            'both' => [
                'direction' => DirectionEnum::BOTH->value,
                'fromCoords' => $fromCoords,
                'toCoords' => $toCoords,
            ],
            'ab' => [
                'direction' => DirectionEnum::A_TO_B->value,
                'fromCoords' => $fromCoords,
                'toCoords' => $toCoords,
            ],
            'ba' => [
                'direction' => DirectionEnum::B_TO_A->value,
                'fromCoords' => $toCoords,
                'toCoords' => $fromCoords,
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeSectionDirection
     */
    public function testComputeSectionDirection(string $direction, Coordinates $fromCoords, Coordinates $toCoords): void
    {
        $this->lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($this->fullLaneGeometry, $fromCoords, $toCoords)
            ->willReturn('section');

        $this->assertSame('section', $this->laneSectionMaker->computeSection(
            $this->fullLaneGeometry,
            $this->roadBanId,
            $this->roadName,
            $this->cityCode,
            $direction,
            fromCoords: $this->fromCoords,
            fromHouseNumber: null,
            fromRoadBanId: null,
            toCoords: $this->toCoords,
            toHouseNumber: null,
            toRoadBanId: null,
        ));
    }
}
