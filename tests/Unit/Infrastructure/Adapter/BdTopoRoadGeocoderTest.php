<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
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

    public function testFindDepartmentalRoads(): void
    {
        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                '
                    SELECT numero, ST_AsGeoJSON(geometrie) AS geometry
                    FROM route_numerotee_ou_nommee
                    WHERE numero LIKE :numero_pattern
                    AND gestionnaire = :gestionnaire
                    AND type_de_route = :type_de_route
                ',
                ['numero_pattern' => 'D32%', 'gestionnaire' => 'Ardennes', 'type_de_route' => 'Départementale'],
            )
            ->willReturn([['numero' => 'D321', 'geometry' => 'test']]);

        $this->assertSame([['roadNumber' => 'D321', 'geometry' => 'test']], $this->roadGeocoder->findDepartmentalRoads('d32', 'Ardennes'));
    }

    public function testFindDepartmentalRoadsUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->findDepartmentalRoads('D32', 'Ardennes');
    }
}
