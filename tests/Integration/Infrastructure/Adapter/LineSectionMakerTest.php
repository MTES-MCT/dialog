<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Adapter;

use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Adapter\LineSectionMaker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LineSectionMakerTest extends KernelTestCase
{
    private $lineGeometry;
    private $em;
    private $lineSectionMaker;

    protected function setUp(): void
    {
        $container = self::getContainer();

        $lineStrings = [
            /*
             * The coordinates below represent the 3 line strings "a", "b" and "c".
             * They all intersect at the point marked as "+" (belongs to all line strings).
             *
             * y (lat)
             * ^
             * 3....b...
             * 2aaa.+..c
             * 1a......c
             * 012345678> x (lon)
             */
            [
                // a
                [1, 1],
                [1, 2],
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
                [8, 2],
                [8, 1],
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

    private function provideComputeSection(): array
    {
        return [
            'pointsOnSameLine' => [
                'fromCoords' => Coordinates::fromLonLat(1, 1), // Belongs to a
                'toCoords' => Coordinates::fromLonLat(3, 2), // Belongs to a
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [1, 1],
                        [1, 2],
                        [2, 2],
                        [3, 2],
                    ],
                ]),
            ],
            'pointsNearSameLine' => [
                'fromCoords' => Coordinates::fromLonLat(6, 1), // Maps to (6, 2) on b
                'toCoords' => Coordinates::fromLonLat(9, 2), // Maps to (8, 2) on b
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [6, 2],
                        [8, 2],
                    ],
                ]),
            ],
            'pointsInInverseOrderComparedToCoordinatesOrder' => [
                'fromCoords' => Coordinates::fromLonLat(3, 2), // 3rd point of a
                'toCoords' => Coordinates::fromLonLat(1, 1), // 1st point of a
                'section' => json_encode([
                    'type' => 'LineString',
                    'coordinates' => [
                        [1, 1],
                        [1, 2],
                        [2, 2],
                        [3, 2],
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
                        [8, 2],
                    ],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider provideComputeSection
     */
    public function testComputeSection(Coordinates $fromCoords, Coordinates $toCoords, string $section): void
    {
        $this->assertSame($section, $this->lineSectionMaker->computeSection(RoadTypeEnum::LANE, $this->lineGeometry, $fromCoords, $toCoords));
    }

    private function provideComputeSectionError(): array
    {
        return [
            'pointsDoNotBelongToSameSegment' => [
                'fromCoords' => Coordinates::fromLonLat(1, 1),
                'toCoords' => Coordinates::fromLonLat(7, 3),
            ],
        ];
    }

    /**
     * @dataProvider provideComputeSectionError
     */
    public function testComputeSectionError(Coordinates $fromCoords, Coordinates $toCoords): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->lineSectionMaker->computeSection(RoadTypeEnum::LANE, $this->lineGeometry, $fromCoords, $toCoords);
    }

    /**
     * @dataProvider provideComputeSectionError
     */
    public function testComputeDepartmentalRoadSectionError(Coordinates $fromCoords, Coordinates $toCoords): void
    {
        $this->expectException(DepartmentalRoadGeocodingFailureException::class);

        $this->lineSectionMaker->computeSection(RoadTypeEnum::DEPARTMENTAL_ROAD, $this->lineGeometry, $fromCoords, $toCoords);
    }
}
