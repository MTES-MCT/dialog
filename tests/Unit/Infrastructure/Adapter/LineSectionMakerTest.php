<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\LineSectionMaker;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class LineSectionMakerTest extends TestCase
{
    private $lineGeometry;
    private $roadName;
    private $cityCode;
    private $fromCoords;
    private $toCoords;
    private $conn;
    private $em;
    private $lineSectionMaker;

    protected function setUp(): void
    {
        $this->lineGeometry = 'geometry';
        $this->roadName = 'Rue du Test';
        $this->cityCode = '01010';
        $this->fromCoords = Coordinates::fromLonLat(1, 41);
        $this->toCoords = Coordinates::fromLonLat(9, 49);

        $this->conn = $this->createMock(Connection::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em
            ->expects(self::atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->conn);

        $this->lineSectionMaker = new LineSectionMaker($this->em);
    }

    public function testComputeSection(): void
    {
        $this->conn
            ->expects(self::exactly(3))
            ->method('fetchAssociative')
            ->withConsecutive(
                [
                    'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :point) AS t',
                    ['geom' => $this->lineGeometry, 'point' => '{"type":"Point","coordinates":[1,41]}'],
                ],
                [
                    'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :point) AS t',
                    ['geom' => $this->lineGeometry, 'point' => '{"type":"Point","coordinates":[9,49]}'],
                ],
                [
                    'SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), :startFraction, :endFraction)) AS line',
                    [
                        'geom' => $this->lineGeometry,
                        'startFraction' => 0.4,
                        'endFraction' => 0.7,
                    ],
                ],
            )
            ->willReturnOnConsecutiveCalls(
                ['t' => 0.4],
                ['t' => 0.7],
                ['line' => 'lineSection'],
            );

        $this->assertSame(
            'lineSection',
            $this->lineSectionMaker->computeSection(
                $this->lineGeometry,
                $this->fromCoords,
                $this->toCoords,
            ),
        );
    }

    public function testComputeSectionInverse(): void
    {
        $this->conn
            ->expects(self::exactly(3))
            ->method('fetchAssociative')
            ->withConsecutive(
                [
                    'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :point) AS t',
                    ['geom' => $this->lineGeometry, 'point' => '{"type":"Point","coordinates":[1,41]}'],
                ],
                [
                    'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :point) AS t',
                    ['geom' => $this->lineGeometry, 'point' => '{"type":"Point","coordinates":[9,49]}'],
                ],
                [
                    'SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), :startFraction, :endFraction)) AS line',
                    [
                        'geom' => $this->lineGeometry,
                        'startFraction' => 0.1,
                        'endFraction' => 0.9,
                    ],
                ],
            )
            ->willReturnOnConsecutiveCalls(
                ['t' => 0.9],
                ['t' => 0.1],
                ['line' => 'lineSection'],
            );

        $this->assertSame(
            'lineSection',
            $this->lineSectionMaker->computeSection(
                $this->lineGeometry,
                $this->fromCoords,
                $this->toCoords,
            ),
        );
    }

    public function testLocatePointError(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->conn
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException($this->createMock(DriverException::class));

        $this->lineSectionMaker->computeSection($this->lineGeometry, $this->fromCoords, $this->toCoords);
    }
}
