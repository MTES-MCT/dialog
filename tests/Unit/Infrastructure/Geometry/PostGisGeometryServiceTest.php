<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Geometry;

use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Persistence\Geometry\PostGisGeometryService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PostGisGeometryServiceTest extends TestCase
{
    public function testLocatePointOnLineTransformExceptionToGeocodingFailureException(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $conn = $this->createMock(Connection::class);
        $stmt = $this->createMock(Statement::class);

        $conn
            ->expects(self::once())
            ->method('prepare')
            ->willReturn($stmt);

        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($conn);

        $stmt
            ->expects(self::once())
            ->method('executeQuery')
            ->willThrowException($this->createMock(DriverException::class));

        $service = new PostGisGeometryService($em);

        $service->locatePointOnLine('line', Coordinates::fromLonLat(3, 45));
    }
}
