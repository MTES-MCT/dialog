<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\EndAbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Exception\StartAbscissaOutOfRangeException;
use App\Application\LineSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Adapter\RoadSectionMaker;
use PHPUnit\Framework\TestCase;

final class RoadSectionMakerTest extends TestCase
{
    private $fromCoords;
    private $toCoords;

    protected function setUp(): void
    {
        $this->fromCoords = Coordinates::fromLonLat(1, 41);
        $this->toCoords = Coordinates::fromLonLat(9, 10);
    }

    public function testComputeSection(): void
    {
        $fullDepartmentalRoadGeometry = 'geometry';
        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromDepartmentCode = null;
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 0;
        $toDepartmentCode = null;
        $toPointNumber = '5';
        $toAbscissa = 150;
        $toSide = 'U';

        $lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);
        $geocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadSectionMaker = new RoadSectionMaker(
            $lineSectionMaker,
            $geocoder,
        );
        $matcher = self::exactly(2);

        $geocoder
            ->expects($matcher)
            ->method('computeReferencePoint')->willReturnCallback(function (...$parameters) use ($matcher, $roadType, $administrator, $roadNumber, $fromDepartmentCode, $fromPointNumber, $fromSide, $fromAbscissa, $toDepartmentCode, $toPointNumber, $toSide, $toAbscissa) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($roadType, $parameters[0]);
                    $this->assertEquals($administrator, $parameters[1]);
                    $this->assertEquals($roadNumber, $parameters[2]);
                    $this->assertEquals($fromDepartmentCode, $parameters[3]);
                    $this->assertEquals($fromPointNumber, $parameters[4]);
                    $this->assertEquals($fromSide, $parameters[5]);
                    $this->assertEquals($fromAbscissa, $parameters[6]);

                    return $this->fromCoords;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($roadType, $parameters[0]);
                    $this->assertEquals($administrator, $parameters[1]);
                    $this->assertEquals($roadNumber, $parameters[2]);
                    $this->assertEquals($toDepartmentCode, $parameters[3]);
                    $this->assertEquals($toPointNumber, $parameters[4]);
                    $this->assertEquals($toSide, $parameters[5]);
                    $this->assertEquals($toAbscissa, $parameters[6]);

                    return $this->toCoords;
                }
            });

        $lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with($fullDepartmentalRoadGeometry, $this->fromCoords, $this->toCoords)
            ->willReturn('section');

        $this->assertSame(
            'section',
            $roadSectionMaker->computeSection(
                $fullDepartmentalRoadGeometry,
                $roadType,
                $administrator,
                $roadNumber,
                $fromDepartmentCode,
                $fromPointNumber,
                $fromSide,
                $fromAbscissa,
                $toDepartmentCode,
                $toPointNumber,
                $toSide,
                $toAbscissa,
            ),
        );
    }

    public function testComputeSectionStartAbscissaOutOfRange(): void
    {
        $this->expectException(StartAbscissaOutOfRangeException::class);

        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $fullDepartmentalRoadGeometry = 'geometry';
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromDepartmentCode = null;
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 1000000000;
        $toDepartmentCode = null;
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
            ->with($roadType, $administrator, $roadNumber, $fromDepartmentCode, $fromPointNumber, $fromSide, $fromAbscissa)
            ->willThrowException(new AbscissaOutOfRangeException($roadType));

        $lineSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $roadType,
            $administrator,
            $roadNumber,
            $fromDepartmentCode,
            $fromPointNumber,
            $fromSide,
            $fromAbscissa,
            $toDepartmentCode,
            $toPointNumber,
            $toSide,
            $toAbscissa,
        );
    }

    public function testComputeSectionEndAbscissaOutOfRange(): void
    {
        $this->expectException(EndAbscissaOutOfRangeException::class);

        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $fullDepartmentalRoadGeometry = 'geometry';
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromDepartmentCode = null;
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 1;
        $toDepartmentCode = null;
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
            ->willReturnCallback(function () use ($matcher, $roadType) {
                if ($matcher->numberOfInvocations() === 1) {
                    return $this->fromCoords;
                }

                throw new AbscissaOutOfRangeException($roadType);
            });

        $lineSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $roadType,
            $administrator,
            $roadNumber,
            $fromDepartmentCode,
            $fromPointNumber,
            $fromSide,
            $fromAbscissa,
            $toDepartmentCode,
            $toPointNumber,
            $toSide,
            $toAbscissa,
        );
    }

    public function testComputeSectionToPointGeocodingError(): void
    {
        self::expectException(RoadGeocodingFailureException::class);

        $fullDepartmentalRoadGeometry = 'geometry';
        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromDepartmentCode = null;
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 0;
        $toDepartmentCode = null;
        $toPointNumber = '5';
        $toAbscissa = 150;
        $toSide = 'U';

        $lineSectionMaker = $this->createMock(LineSectionMakerInterface::class);
        $geocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadSectionMaker = new RoadSectionMaker(
            $lineSectionMaker,
            $geocoder,
        );

        $matcher = self::exactly(2);
        $geocoder
            ->expects($matcher)
            ->method('computeReferencePoint')
            ->willReturnCallback(function () use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    return $this->fromCoords;
                }
                throw new GeocodingFailureException('oops');
            });

        $lineSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $roadType,
            $administrator,
            $roadNumber,
            $fromDepartmentCode,
            $fromPointNumber,
            $fromSide,
            $fromAbscissa,
            $toDepartmentCode,
            $toPointNumber,
            $toSide,
            $toAbscissa,
        );
    }

    public function testComputeSectionLineSectionGeocodingError(): void
    {
        self::expectException(RoadGeocodingFailureException::class);

        $fullDepartmentalRoadGeometry = 'geometry';
        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $administrator = 'Ardèche';
        $roadNumber = 'D110';
        $fromDepartmentCode = null;
        $fromPointNumber = '1';
        $fromSide = 'U';
        $fromAbscissa = 0;
        $toDepartmentCode = null;
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
            ->expects(self::exactly(2))
            ->method('computeReferencePoint')
            ->willReturnOnConsecutiveCalls($this->fromCoords, $this->toCoords);

        $lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new GeocodingFailureException('oops'));

        $roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $roadType,
            $administrator,
            $roadNumber,
            $fromDepartmentCode,
            $fromPointNumber,
            $fromSide,
            $fromAbscissa,
            $toDepartmentCode,
            $toPointNumber,
            $toSide,
            $toAbscissa,
        );
    }
}
