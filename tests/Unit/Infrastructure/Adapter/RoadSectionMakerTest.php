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
use App\Domain\Regulation\Enum\DirectionEnum;
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
        $direction = DirectionEnum::BOTH->value;

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
                [$roadType, $administrator, $roadNumber, $fromDepartmentCode, $fromPointNumber, $fromSide, $fromAbscissa],
                [$roadType, $administrator, $roadNumber, $toDepartmentCode, $toPointNumber, $toSide, $toAbscissa],
            )
            ->willReturnOnConsecutiveCalls($this->fromCoords, $this->toCoords);

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
                $direction,
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
        $direction = DirectionEnum::BOTH->value;

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
            $direction,
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
        $direction = DirectionEnum::BOTH->value;

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
                if ($matcher->getInvocationCount() === 1) {
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
            $direction,
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
        $direction = DirectionEnum::BOTH->value;

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
                if ($matcher->getInvocationCount() === 1) {
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
            $direction,
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
        $direction = DirectionEnum::BOTH->value;

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
            $direction,
        );
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
            ->withConsecutive(
                [$roadType, $administrator, $roadNumber, $fromDepartmentCode, $fromPointNumber, $fromSide, $fromAbscissa],
                [$roadType, $administrator, $roadNumber, $toDepartmentCode, $toPointNumber, $toSide, $toAbscissa],
            )
            ->willReturnOnConsecutiveCalls($this->fromCoords, $this->toCoords);

        $lineSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with('geometry', $fromCoords, $toCoords)
            ->willReturn('section');

        $this->assertSame('section', $roadSectionMaker->computeSection(
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
            $direction,
        ));
    }
}
