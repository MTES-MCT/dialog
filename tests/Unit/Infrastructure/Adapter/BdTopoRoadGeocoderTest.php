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

        $this->roadGeocoder->computeRoadLine('Rue du Test', '01234');
    }

    public function testfindRoadsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findRoads(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'D32', 'Ardennes');
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

    public function testFindRoadNamesUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findRoadNames('Rue Eugène Berthoud', '93070');
    }

    public function testFindIntersectingRoadNamesUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findIntersectingNamedStreets('', 'Rue Eugène Berthoud', '93070');
    }

    public function testComputeIntersectionUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeIntersection('Rue Eugène Berthoud', 'Rue du Test', '93070');
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
}
