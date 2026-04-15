<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Regulation\Fragments;

use App\Application\Exception\GeocodingAddressNotFoundException;
use App\Application\LaneSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Controller\Regulation\Fragments\GetLocationGeometryController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class GetLocationGeometryControllerTest extends TestCase
{
    private RoadGeocoderInterface&MockObject $roadGeocoder;
    private LaneSectionMakerInterface&MockObject $laneSectionMaker;
    private RoadSectionMakerInterface&MockObject $roadSectionMaker;
    private LoggerInterface&MockObject $logger;
    private GetLocationGeometryController $controller;

    protected function setUp(): void
    {
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->laneSectionMaker = $this->createMock(LaneSectionMakerInterface::class);
        $this->roadSectionMaker = $this->createMock(RoadSectionMakerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new GetLocationGeometryController(
            $this->roadGeocoder,
            $this->laneSectionMaker,
            $this->roadSectionMaker,
            $this->logger,
        );
    }

    public function testLaneNoRoadBanId(): void
    {
        $this->roadGeocoder->expects(self::never())->method('computeRoadLine');

        $response = ($this->controller)(roadType: RoadTypeEnum::LANE, roadBanId: null);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testLaneFullGeometry(): void
    {
        $geometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('ban123')
            ->willReturn($geometry);

        $this->laneSectionMaker->expects(self::never())->method('computeSection');

        $response = ($this->controller)(roadType: RoadTypeEnum::LANE, roadBanId: 'ban123');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($geometry, $response->getContent());
    }

    public function testLaneFullGeometryNoStartPoint(): void
    {
        $geometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('ban123')
            ->willReturn($geometry);

        $this->laneSectionMaker->expects(self::never())->method('computeSection');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            toHouseNumber: '10',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLaneFullGeometryNoEndPoint(): void
    {
        $geometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('ban123')
            ->willReturn($geometry);

        $this->laneSectionMaker->expects(self::never())->method('computeSection');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromHouseNumber: '5',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLaneWithSection(): void
    {
        $fullGeometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';
        $sectionGeometry = '{"type":"LineString","coordinates":[[1.5,2.5],[2.5,3.5]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('ban123')
            ->willReturn($fullGeometry);

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullGeometry,
                'ban123',
                'Rue Test',
                '59000',
                DirectionEnum::BOTH->value,
                null,
                '5',
                null,
                null,
                '10',
                null,
            )
            ->willReturn($sectionGeometry);

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromHouseNumber: '5',
            toHouseNumber: '10',
            roadName: 'Rue Test',
            cityCode: '59000',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($sectionGeometry, $response->getContent());
    }

    public function testLaneWithSectionFromRoadBanIds(): void
    {
        $fullGeometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';
        $sectionGeometry = '{"type":"LineString","coordinates":[[1.5,2.5],[2.5,3.5]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('ban123')
            ->willReturn($fullGeometry);

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullGeometry,
                'ban123',
                'Rue Test',
                '59000',
                DirectionEnum::BOTH->value,
                null,
                null,
                'fromBan456',
                null,
                null,
                'toBan789',
            )
            ->willReturn($sectionGeometry);

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromRoadBanId: 'fromBan456',
            toRoadBanId: 'toBan789',
            roadName: 'Rue Test',
            cityCode: '59000',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($sectionGeometry, $response->getContent());
    }

    public function testLaneComputeRoadLineException(): void
    {
        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->willThrowException(new \RuntimeException('error'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        $response = ($this->controller)(roadType: RoadTypeEnum::LANE, roadBanId: 'ban123');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testLaneSectionComputeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $fullGeometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->willReturn($fullGeometry);

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new \RuntimeException('section error'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromHouseNumber: '5',
            toHouseNumber: '10',
            roadName: 'Rue Test',
            cityCode: '59000',
        );
    }

    public function testLaneGeocodingAddressNotFound(): void
    {
        $fullGeometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->willReturn($fullGeometry);

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new GeocodingAddressNotFoundException('not found'));

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromHouseNumber: '5',
            toHouseNumber: '10',
            roadName: 'Rue Test',
            cityCode: '59000',
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testNumberedRoadNoAdministrator(): void
    {
        $this->roadGeocoder->expects(self::never())->method('computeRoad');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: null,
            roadNumber: 'D906',
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testNumberedRoadNoRoadNumber(): void
    {
        $this->roadGeocoder->expects(self::never())->method('computeRoad');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: null,
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testNumberedRoadFullGeometry(): void
    {
        $geometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->with(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'Ardèche', 'D906')
            ->willReturn($geometry);

        $this->roadSectionMaker->expects(self::never())->method('computeSection');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($geometry, $response->getContent());
    }

    public function testNumberedRoadFullGeometryMissingFromPointNumber(): void
    {
        $geometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->willReturn($geometry);

        $this->roadSectionMaker->expects(self::never())->method('computeSection');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            fromPointNumber: null,
            toPointNumber: '35',
            fromSide: 'U',
            toSide: 'U',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNumberedRoadFullGeometryMissingToSide(): void
    {
        $geometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->willReturn($geometry);

        $this->roadSectionMaker->expects(self::never())->method('computeSection');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            fromPointNumber: '34',
            toPointNumber: '35',
            fromSide: 'U',
            toSide: null,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNumberedRoadWithSection(): void
    {
        $fullGeometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';
        $sectionGeometry = '{"type":"LineString","coordinates":[[1.5,2.5],[2.5,3.5]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->with(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'Ardèche', 'D906')
            ->willReturn($fullGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullGeometry,
                RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'Ardèche',
                'D906',
                null,  // fromDepartmentCode (no ## separator)
                '34',  // fromPointNumber
                'U',
                0,
                null,  // toDepartmentCode
                '35',  // toPointNumber
                'U',
                500,
                DirectionEnum::BOTH->value,
            )
            ->willReturn($sectionGeometry);

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            fromPointNumber: '34',
            toPointNumber: '35',
            fromSide: 'U',
            toSide: 'U',
            fromAbscissa: 0,
            toAbscissa: 500,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($sectionGeometry, $response->getContent());
    }

    public function testNumberedRoadWithSectionAndDepartmentCode(): void
    {
        $fullGeometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';
        $sectionGeometry = '{"type":"LineString","coordinates":[[1.5,2.5],[2.5,3.5]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->willReturn($fullGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullGeometry,
                RoadTypeEnum::NATIONAL_ROAD->value,
                'DIR Ouest',
                'N12',
                '22',   // fromDepartmentCode
                '122',  // fromPointNumber
                'D',
                0,
                '29',   // toDepartmentCode
                '200',  // toPointNumber
                'G',
                100,
                'A_TO_B',
            )
            ->willReturn($sectionGeometry);

        $response = ($this->controller)(
            roadType: RoadTypeEnum::NATIONAL_ROAD,
            administrator: 'DIR Ouest',
            roadNumber: 'N12',
            fromPointNumber: '22##122',
            toPointNumber: '29##200',
            fromSide: 'D',
            toSide: 'G',
            fromAbscissa: 0,
            toAbscissa: 100,
            direction: 'A_TO_B',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNumberedRoadComputeRoadException(): void
    {
        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->willThrowException(new \RuntimeException('error'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testNumberedRoadSectionComputeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $fullGeometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->willReturn($fullGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new \RuntimeException('section error'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            fromPointNumber: '34',
            toPointNumber: '35',
            fromSide: 'U',
            toSide: 'U',
        );
    }

    public function testNumberedRoadGeocodingAddressNotFound(): void
    {
        $fullGeometry = '{"type":"MultiLineString","coordinates":[[[1,2],[3,4]]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->willReturn($fullGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new GeocodingAddressNotFoundException('not found'));

        $response = ($this->controller)(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            fromPointNumber: '34',
            toPointNumber: '35',
            fromSide: 'U',
            toSide: 'U',
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUnsupportedRoadType(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);

        ($this->controller)(roadType: RoadTypeEnum::RAW_GEOJSON);
    }

    public function testLaneDefaultDirection(): void
    {
        $fullGeometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';
        $sectionGeometry = '{"type":"LineString","coordinates":[[1.5,2.5]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->willReturn($fullGeometry);

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullGeometry,
                'ban123',
                '',
                '',
                DirectionEnum::BOTH->value,
                null,
                '5',
                null,
                null,
                '10',
                null,
            )
            ->willReturn($sectionGeometry);

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromHouseNumber: '5',
            toHouseNumber: '10',
            direction: null,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLaneCustomDirection(): void
    {
        $fullGeometry = '{"type":"LineString","coordinates":[[1,2],[3,4]]}';
        $sectionGeometry = '{"type":"LineString","coordinates":[[1.5,2.5]]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->willReturn($fullGeometry);

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullGeometry,
                'ban123',
                '',
                '',
                'A_TO_B',
                null,
                '5',
                null,
                null,
                '10',
                null,
            )
            ->willReturn($sectionGeometry);

        $response = ($this->controller)(
            roadType: RoadTypeEnum::LANE,
            roadBanId: 'ban123',
            fromHouseNumber: '5',
            toHouseNumber: '10',
            direction: 'A_TO_B',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
