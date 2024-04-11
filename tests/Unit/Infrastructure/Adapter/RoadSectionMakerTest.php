<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\EndAbscissaOutOfRangeException;
use App\Application\Exception\StartAbscissaOutOfRangeException;
use App\Application\LineSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\RoadSectionMaker;
use PHPUnit\Framework\TestCase;

final class RoadSectionMakerTest extends TestCase
{
    public function testComputeSection(): void
    {
        $fullDepartmentalRoadGeometry = 'geometry';
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 0;
        $toPointNumber = '5';
        $toAbscissa = 150;
        $toSide = 'U';

        $fromCoords = Coordinates::fromLonLat(1, 41);
        $toCoords = Coordinates::fromLonLat(9, 10);

        $lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);
        $geocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadSectionMaker = new RoadSectionMaker(
            $lineSectionMaker,
            $geocoder,
        );

        $geocoder
            ->expects(self::exactly(2))
            ->method('computeReferencePoint')
            ->withConsecutive(
                [$fullDepartmentalRoadGeometry, $administrator, $roadNumber, $fromPointNumber, $fromSide, $fromAbscissa],
                [$fullDepartmentalRoadGeometry, $administrator, $roadNumber, $toPointNumber, $toSide, $toAbscissa],
            )
            ->willReturnOnConsecutiveCalls($fromCoords, $toCoords);

        $lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($fullDepartmentalRoadGeometry, $fromCoords, $toCoords)
            ->willReturn('section');

        $this->assertSame(
            'section',
            $roadSectionMaker->computeSection(
                $fullDepartmentalRoadGeometry,
                $administrator,
                $roadNumber,
                $fromPointNumber,
                $fromSide,
                $fromAbscissa,
                $toPointNumber,
                $toSide,
                $toAbscissa,
            ),
        );
    }

    public function testComputeSectionStartAbscissaOutOfRange(): void
    {
        $this->expectException(StartAbscissaOutOfRangeException::class);

        $fullDepartmentalRoadGeometry = 'geometry';
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 1000000000;
        $toPointNumber = '5';
        $toAbscissa = 150;
        $toSide = 'U';

        $lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);
        $geocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadSectionMaker = new RoadSectionMaker(
            $lineSectionMaker,
            $geocoder,
        );

        $geocoder
            ->expects(self::once())
            ->method('computeReferencePoint')
            ->with($fullDepartmentalRoadGeometry, $administrator, $roadNumber, $fromPointNumber, $fromSide, $fromAbscissa)
            ->willThrowException(new AbscissaOutOfRangeException());

        $roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $administrator,
            $roadNumber,
            $fromPointNumber,
            $fromSide,
            $fromAbscissa,
            $toPointNumber,
            $toSide,
            $toAbscissa,
        );
    }

    public function testComputeSectionEndAbscissaOutOfRange(): void
    {
        $this->expectException(EndAbscissaOutOfRangeException::class);

        $fromCoords = Coordinates::fromLonLat(1, 41);

        $fullDepartmentalRoadGeometry = 'geometry';
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 1;
        $toPointNumber = '5';
        $toAbscissa = 15000000;
        $toSide = 'U';

        $lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);
        $geocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadSectionMaker = new RoadSectionMaker(
            $lineSectionMaker,
            $geocoder,
        );

        $matcher = $this->exactly(2);
        $geocoder
            ->expects($matcher)
            ->method('computeReferencePoint')
            ->willReturnCallback(function () use ($matcher, $fromCoords) {
                if ($matcher->getInvocationCount() === 1) {
                    return $fromCoords;
                }

                throw new AbscissaOutOfRangeException();
            });

        $roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $administrator,
            $roadNumber,
            $fromPointNumber,
            $fromSide,
            $fromAbscissa,
            $toPointNumber,
            $toSide,
            $toAbscissa,
        );
    }
}
