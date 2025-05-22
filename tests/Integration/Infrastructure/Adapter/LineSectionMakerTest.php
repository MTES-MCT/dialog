<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\LineSectionMaker;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LineSectionMakerTest extends KernelTestCase
{
    /** @var Connection */
    private $bdtopoConnection;
    private $lineGeometry;
    private $em;
    private $lineSectionMaker;

    protected function setUp(): void
    {
        $container = self::getContainer();

        $this->bdtopoConnection = $container->get('doctrine.dbal.bdtopo2025_connection');

        $lineStrings = [
            /*
             * The coordinates below represent the line strings "a", "b", "c" and "d".
             * Line strings "a", "b" and "c" all intersect at the point marked as "+" (belongs to all line strings).
             * There is a gap between "c" and "d".
             *
             * y (lat)
             * ^
             * 3....b.......
             * 2aaa-+-c....d
             * 1a.....c.....
             * 0123456789012> x (lon)
             */
            [
                // a (1st segment)
                [1, 1],
                [1, 2],
                [2, 2],
            ],
            [
                // a (2nd segment)
                [2, 2],
                [3, 2],
                [5, 2],
            ],
            [
                // b
                [5, 2],
                [5, 3],
            ],
            [
                // c
                [5, 2],
                [7, 2],
                [7, 1],
            ],
            [
                // d
                [12, 2],
                [13, 2],
            ],
        ];

        $this->lineGeometry = json_encode(
            [
                'type' => 'MultiLineString',
                'coordinates' => $lineStrings,
            ],
        );

        $this->em = $container->get(EntityManagerInterface::class);
        $this->lineSectionMaker = new LineSectionMaker($this->em);
    }

    /**
     * Return the tolerance radius to apply so that $pointA and $pointB are exactly equally distant to $point.
     */
    private static function getThresholdTolerance(Connection $bdtopoConnection, Coordinates $point, Coordinates $pointA, Coordinates $pointB): float
    {
        $distA = $bdtopoConnection->fetchOne(
            'SELECT ST_Distance(ST_GeomFromGeoJSON(:p)::geography, ST_GeomFromGeoJSON(:a)::geography)',
            [
                'p' => $point->asGeoJSON(),
                'a' => $pointA->asGeoJSON(),
            ],
        );

        $distB = $bdtopoConnection->fetchOne(
            'SELECT ST_Distance(ST_GeomFromGeoJSON(:p)::geography, ST_GeomFromGeoJSON(:b)::geography)',
            [
                'p' => $point->asGeoJSON(),
                'b' => $pointB->asGeoJSON(),
            ],
        );

        return abs($distB - $distA);
    }

    private function provideComputeSection(): array
    {
        return [
            'pointsOnSameLineDifferentSegments' => [
                'fromCoords' => Coordinates::fromLonLat(1, 1), // Belongs to a (1st segment)
                'toCoords' => Coordinates::fromLonLat(3, 2), // Belongs to a (2nd segment)
                'section' => json_encode([
                    'type' => 'GeometryCollection',
                    'geometries' => [
                        [
                            'type' => 'LineString',
                            'coordinates' => [
                                [2, 2],
                                [3, 2],
                            ],
                        ],
                        [
                            'type' => 'LineString',
                            'coordinates' => [
                                [1, 1],
                                [1, 2],
                                [2, 2],
                            ],
                        ],
                    ],
                ]),
            ],
            'pointsNearSameLine' => [
                'fromCoords' => Coordinates::fromLonLat(6, 1), // Maps to (6, 2) and (7, 1) on c
                'toCoords' => Coordinates::fromLonLat(9, 2), // Maps to (7, 2) on c
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [6, 2],
                        [7, 2],
                    ],
                ]),
            ],
            'pointsInInverseOrderComparedToCoordinatesOrder' => [
                'fromCoords' => Coordinates::fromLonLat(1, 2), // 3rd point of a (1st segment)
                'toCoords' => Coordinates::fromLonLat(1, 1), // 1st point of a (1st segment)
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [1, 1],
                        [1, 2],
                    ],
                ]),
            ],
            'pointsNearSameLineWithOneMatchingTwoOrMoreLines' => [
                'fromCoords' => Coordinates::fromLonLat(5, 1), // Maps to (5, 2) (intersection point) on a, b, c
                'toCoords' => Coordinates::fromLonLat(9, 2), // Maps to (8, 2) on b
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [5, 2],
                        [7, 2],
                    ],
                ]),
            ],
            'pointNearSameLineWithinTolerance' => [
                'fromCoords' => Coordinates::fromLonLat(7, 1),
                'toCoords' => Coordinates::fromLonLat(9.6, 2), // P
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [7, 2],
                        [7, 1],
                    ],
                ]),
                'getTolerance' => fn ($bdtopoConnection) => 1.01 // Just above
                    * self::getThresholdTolerance(
                        $bdtopoConnection,
                        point: Coordinates::fromLonLat(9.6, 2), // P
                        pointA: Coordinates::fromLonLat(7, 2), // Point of c that is closest to P
                        pointB: Coordinates::fromLonLat(12, 2), // Point of d that is closest to P
                    ),
            ],
        ];
    }

    /**
     * @dataProvider provideComputeSection
     */
    public function testComputeSection(Coordinates $fromCoords, Coordinates $toCoords, string $section, ?\Closure $getTolerance = null): void
    {
        $tolerance = $getTolerance ? $getTolerance($this->bdtopoConnection) : 1;

        $this->assertSame($section, $this->lineSectionMaker->computeSection($this->lineGeometry, $fromCoords, $toCoords, $tolerance));
    }

    private function provideComputeSectionError(): array
    {
        return [
            'pointsDoNotMapToSameSegment' => [
                'fromCoords' => Coordinates::fromLonLat(1, 1), // Belongs to a
                'toCoords' => Coordinates::fromLonLat(7, 3), // Maps to c
            ],
            'pointsMapOutsideTolerance' => [
                'fromCoords' => Coordinates::fromLonLat(7, 1),
                'toCoords' => Coordinates::fromLonLat(9.6, 2), // P
                'getTolerance' => fn ($bdtopoConnection) => 0.99 // Just below
                    * self::getThresholdTolerance(
                        $bdtopoConnection,
                        point: Coordinates::fromLonLat(9.6, 2), // P
                        pointA: Coordinates::fromLonLat(7, 2), // Point of c that is closest to P
                        pointB: Coordinates::fromLonLat(12, 2), // Point of d that is closest to P
                    ),
            ],
        ];
    }

    /**
     * @dataProvider provideComputeSectionError
     */
    public function testComputeSectionError(Coordinates $fromCoords, Coordinates $toCoords, ?\Closure $getTolerance = null): void
    {
        $tolerance = $getTolerance ? $getTolerance($this->bdtopoConnection) : 1;

        $this->expectException(GeocodingFailureException::class);
        $this->lineSectionMaker->computeSection($this->lineGeometry, $fromCoords, $toCoords, $tolerance);
    }
}
