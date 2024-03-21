<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\LineSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Adapter\DepartmentalRoadSectionMaker;
use PHPUnit\Framework\TestCase;

final class DepartmentalRoadSectionMakerTest extends TestCase
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
        $departmentalRoadSectionMaker = new DepartmentalRoadSectionMaker(
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
            ->with(RoadTypeEnum::DEPARTMENTAL_ROAD, $fullDepartmentalRoadGeometry, $fromCoords, $toCoords)
            ->willReturn('section');

        $this->assertSame(
            'section',
            $departmentalRoadSectionMaker->computeSection(
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
}
