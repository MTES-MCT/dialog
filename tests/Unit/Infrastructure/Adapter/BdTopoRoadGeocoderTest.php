<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\BdTopoRoadGeocoder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class BdTopoRoadGeocoderTest extends TestCase
{
    private $conn;
    private $roadGeocoder;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(Connection::class);
        $this->roadGeocoder = new BdTopoRoadGeocoder($this->conn);
    }

    public function testComputeRoadLine(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                '
                    SELECT ST_AsGeoJSON(geometrie) AS geometry
                    FROM voie_nommee
                    WHERE f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:nom_minuscule)
                    AND code_insee = :code_insee
                    LIMIT 1
                ',
                ['nom_minuscule' => 'Rue du Test', 'code_insee' => '01234'],
            )
            ->willReturn([['geometry' => 'test']]);

        $this->assertSame('test', $this->roadGeocoder->computeRoadLine('Rue du Test', '01234'));
    }

    public function testComputeRoadLineNoResult(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->roadGeocoder->computeRoadLine('Rue du Test', '01234');
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

    public function testfindRoads(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                '
                    SELECT numero
                    FROM route_numerotee_ou_nommee
                    WHERE numero LIKE :numero_pattern
                    AND gestionnaire = :gestionnaire
                    AND type_de_route = :type_de_route
                ',
                ['numero_pattern' => 'D32%', 'gestionnaire' => 'Ardennes', 'type_de_route' => 'Départementale'],
            )
            ->willReturn([['numero' => 'D321']]);

        $this->assertSame([['roadNumber' => 'D321']], $this->roadGeocoder->findRoads('d32', 'Ardennes'));
    }

    public function testfindRoadsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findRoads('D32', 'Ardennes');
    }

    public function testComputeRoad(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                '
                    SELECT ST_AsGeoJSON(geometrie) AS geometry
                    FROM route_numerotee_ou_nommee
                    WHERE numero = :numero
                    AND gestionnaire = :gestionnaire
                    AND type_de_route = :type_de_route
                    LIMIT 1
                ',
                [
                    'numero' => 'D110',
                    'gestionnaire' => 'Ardèche',
                    'type_de_route' => 'Départementale',
                ],
            )
            ->willReturn([['geometry' => 'test']]);

        $this->assertSame('test', $this->roadGeocoder->computeRoad('D110', 'Ardèche'));
    }

    public function testComputeRoadNoResults(): void
    {
        $this->expectException(RoadGeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->assertSame('test', $this->roadGeocoder->computeRoad('D110', 'Ardèche'));
    }

    public function testComputeRoadUnexpectedError(): void
    {
        $this->expectException(RoadGeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeRoad('D32', 'Ardennes');
    }

    public function testComputeReferencePoint(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with(
                '
                    WITH pr as (
                        SELECT abscisse + :abscisse as abscisse
                        FROM point_de_repere
                        WHERE route = :route
                        AND gestionnaire = :gestionnaire
                        AND cote = :cote
                        AND numero = :numero
                        LIMIT 1
                    )
                    SELECT ST_AsGeoJSON(
                        ST_LocateAlong(
                            ST_AddMeasure(
                                ST_LineMerge(:geom),
                                0,
                                ST_Length(
                                    -- Convert to meters
                                    ST_Transform(
                                        ST_GeomFromGeoJSON(:geom),
                                        2154
                                    )
                                )
                            ),
                            pr.abscisse
                        )
                    ) as point
                    FROM pr
                ',
                [
                    'geom' => 'geom',
                    'route' => 'D32',
                    'gestionnaire' => 'Ardennes',
                    'numero' => '1',
                    'abscisse' => 100,
                    'cote' => 'U',
                ],
            )
            ->willReturn(['point' => '{"type":"MultiPoint","coordinates":[[3.953779408,44.771647561]]}']);

        $this->assertEquals(Coordinates::fromLonLat(3.953779408, 44.771647561), $this->roadGeocoder->computeReferencePoint('geom', 'Ardennes', 'D32', '1', 'U', 100));
    }

    public function testComputeReferencePointAbscissaOutOfRange(): void
    {
        $this->expectException(AbscissaOutOfRangeException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(['point' => '{"type":"MultiPoint","coordinates":[]}']);

        $this->roadGeocoder->computeReferencePoint('geom', 'Ardennes', 'D32', '1', 'U', 1000000);
    }

    public function testComputeReferencePointNoResults(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->roadGeocoder->computeReferencePoint('geom', 'Ardennes', 'D32', '1', 'U', 100);
    }

    public function testComputeReferencePointUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeReferencePoint('geom', 'Ardennes', 'D32', '1', 'U', 0);
    }

    public function testFindRoadNames(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([[
                'road_name' => 'Rue Eugène Berthoud',
            ]]);

        $this->assertEquals([
            [
                'value' => 'Rue Eugène Berthoud',
                'label' => 'Rue Eugène Berthoud',
            ],
        ], $this->roadGeocoder->findRoadNames('Rue Eugène Berthoud', '93070'));
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
}
