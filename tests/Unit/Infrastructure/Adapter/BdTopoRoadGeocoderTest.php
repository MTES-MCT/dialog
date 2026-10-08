<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\IntersectionGeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Organization\View\MapBboxView;
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

    public function testFindCityBboxUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findCityBbox('59606');
    }

    public function testFindCityBboxNoResult(): void
    {
        // ST_Extent vaut NULL quand la commune n'a aucune voie nommée dans BD TOPO.
        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with(self::anything(), ['city_codes' => ['59606']], self::anything())
            ->willReturn(['min_lon' => null, 'min_lat' => null, 'max_lon' => null, 'max_lat' => null]);

        $this->assertNull($this->roadGeocoder->findCityBbox('59606'));
    }

    public function testFindCityBboxExpandsLyonToArrondissements(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with(
                self::anything(),
                ['city_codes' => ['69381', '69382', '69383', '69384', '69385', '69386', '69387', '69388', '69389']],
                self::anything(),
            )
            ->willReturn(['min_lon' => '4.772', 'min_lat' => '45.711', 'max_lon' => '4.898', 'max_lat' => '45.808']);

        $this->assertEquals(
            new MapBboxView(minLon: 4.772, minLat: 45.711, maxLon: 4.898, maxLat: 45.808),
            $this->roadGeocoder->findCityBbox('69123'),
        );
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

    public function testComputeReferencePointsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeReferencePoints('DIR Ouest', 'N12');
    }

    public function testComputeReferencePoints(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'point_number' => '1',
                    'department_code' => '35',
                    'geometry' => '{"type":"Point","coordinates":[-2.1,48.2]}',
                    'num_departments' => 1,
                ],
                [
                    'point_number' => '2',
                    'department_code' => '22',
                    'geometry' => '{"type":"Point","coordinates":[-2.2,48.3]}',
                    'num_departments' => 2,
                ],
                [
                    // Géométrie invalide : la Feature est ignorée.
                    'point_number' => '3',
                    'department_code' => '22',
                    'geometry' => 'null',
                    'num_departments' => 2,
                ],
            ]);

        $result = $this->roadGeocoder->computeReferencePoints('DIR Ouest', 'N12');

        $this->assertSame([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => ['type' => 'Point', 'coordinates' => [-2.1, 48.2]],
                    'properties' => [
                        'pointNumber' => '1',
                        'departmentCode' => '35',
                        'label' => '1',
                    ],
                ],
                [
                    'type' => 'Feature',
                    'geometry' => ['type' => 'Point', 'coordinates' => [-2.2, 48.3]],
                    'properties' => [
                        'pointNumber' => '2',
                        'departmentCode' => '22',
                        'label' => '2 (dép 22)',
                    ],
                ],
            ],
        ], json_decode($result, associative: true));
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

    public function testComputeIntersectionNoRows(): void
    {
        $this->expectException(IntersectionGeocodingFailureException::class);
        $this->expectExceptionMessage('no intersection exists between roadBanId="93070_1234" and otherRoadBanId="93070_5678"');

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->roadGeocoder->computeIntersection('93070_1234', '93070_5678');
    }

    public function testComputeIntersectionNullCoordinates(): void
    {
        $this->expectException(IntersectionGeocodingFailureException::class);
        $this->expectExceptionMessage('no intersection found between roadBanId="93070_1234" and otherRoadBanId="93070_5678"');

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([['x' => null, 'y' => null]]);

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

    public function testFindNearbyStreetsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessage('Nearby streets query has failed');

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->roadGeocoder->findNearbyStreets('{"type":"Point","coordinates":[2.35,48.85]}');
    }
}
