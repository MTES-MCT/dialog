<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
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

        $this->roadGeocoder->findRoads('D32', 'Ardennes');
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

    public function testComputeReferencePointUnexpectedError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Some network error'));

        $this->roadGeocoder->computeReferencePoint('geom', 'Ardennes', 'D32', '1', 'U', 0);
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
