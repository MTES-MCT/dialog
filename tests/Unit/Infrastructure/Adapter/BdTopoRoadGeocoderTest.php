<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Adapter\BdTopoRoadGeocoder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class BdTopoRoadGeocoderTest extends TestCase
{
    private $conn;
    private BdTopoRoadGeocoder $roadGeocoder;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(Connection::class);
        $this->roadGeocoder = new BdTopoRoadGeocoder($this->conn);
    }

    public function testComputeRoadLineUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeRoadLine('01234');
    }

    public function testfindRoadsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findRoads('D32', RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'Ardennes');
    }

    public function testComputeRoadUnexpectedError(): void
    {
        $this->expectException(RoadGeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeRoad(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'Ardennes', 'D32');
    }

    public function testComputeReferencePointUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeReferencePoint(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'Ardennes', 'D32', null, '1', 'U', 0);
    }

    public function testFindReferencePointsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findReferencePoints('1', 'DIR Ouest', 'N12');
    }

    public function testFindIntersectingNamedStreetsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findIntersectingNamedStreets('93070_1234', '93070');
    }

    public function testComputeIntersectionUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeIntersection('93070_1234', '93070_5678');
    }

    public function testFindSectionsInAreaUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findSectionsInArea('<geometry>');
    }

    public function testFindSectionsInAreaNoResult(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(['geom' => null]);

        $this->assertSame('{"type":"GeometryCollection","geometries":[]}', $this->roadGeocoder->findSectionsInArea('<geometry>'));
    }

    public function testConvertPolygonRoadToLinesUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->convertPolygonRoadToLines('<geometry>');
    }

    public function testConvertPolygonToRoadLinesNoResult(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(['geom' => null]);

        $this->assertSame('{"type":"GeometryCollection","geometries":[]}', $this->roadGeocoder->convertPolygonRoadToLines('<geometry>'));
    }

    public function testComputeRoadLineFromNameSuccess(): void
    {
        $roadName = 'Rue de la Paix';
        $inseeCode = '75056';
        $expectedGeometry = '{"type":"LineString","coordinates":[[2.3522,48.8566],[2.3523,48.8567]]}';

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'geometry' => $expectedGeometry,
                ],
            ]);

        $result = $this->roadGeocoder->computeRoadLineFromName($roadName, $inseeCode);

        $this->assertSame($expectedGeometry, $result);
    }

    public function testComputeRoadLineFromNameNotFound(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessage("no result found for roadName='Unknown Street' and inseeCode='75056'");

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->roadGeocoder->computeRoadLineFromName('Unknown Street', '75056');
    }

    public function testComputeRoadLineFromNameEmptyGeometry(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessage("no result found for roadName='Rue de la Paix' and inseeCode='75056'");

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'geometry' => null,
                ],
            ]);

        $this->roadGeocoder->computeRoadLineFromName('Rue de la Paix', '75056');
    }

    public function testComputeRoadLineFromNameDatabaseError(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessage('Road line from name query has failed');

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->roadGeocoder->computeRoadLineFromName('Rue de la Paix', '75056');
    }
}
