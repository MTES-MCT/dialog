<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Adapter;

use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\LineOrienter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LineOrienterTest extends KernelTestCase
{
    private LineOrienter $orienter;

    protected function setUp(): void
    {
        $this->orienter = new LineOrienter(self::getContainer()->get(EntityManagerInterface::class));
    }

    private function provideOrient(): array
    {
        $a = Coordinates::fromLonLat(1, 1);
        $b = Coordinates::fromLonLat(7, 2);

        return [
            'ligne déjà orientée : inchangée' => [
                'geometry' => '{"type":"LineString","coordinates":[[1,1],[1,2],[5,2],[7,2]]}',
                'expected' => '{"type":"LineString","coordinates":[[1,1],[1,2],[5,2],[7,2]]}',
            ],
            'ligne à contresens : retournée' => [
                'geometry' => '{"type":"LineString","coordinates":[[7,2],[5,2],[1,2],[1,1]]}',
                'expected' => '{"type":"LineString","coordinates":[[1,1],[1,2],[5,2],[7,2]]}',
            ],
            'morceaux connectés dans le désordre : fusionnés et orientés' => [
                'geometry' => '{"type":"MultiLineString","coordinates":[[[7,2],[5,2]],[[1,1],[1,2]],[[1,2],[5,2]]]}',
                'expected' => '{"type":"LineString","coordinates":[[1,1],[1,2],[5,2],[7,2]]}',
            ],
            'collection de lignes (ancien format) : fusionnée' => [
                'geometry' => '{"type":"GeometryCollection","geometries":[{"type":"LineString","coordinates":[[5,2],[7,2]]},{"type":"LineString","coordinates":[[1,1],[1,2],[5,2]]}]}',
                'expected' => '{"type":"LineString","coordinates":[[1,1],[1,2],[5,2],[7,2]]}',
            ],
            'morceaux disjoints : chacun orienté puis ordonnés depuis A' => [
                'geometry' => '{"type":"MultiLineString","coordinates":[[[7,2],[6,2]],[[2,1],[1,1]]]}',
                'expected' => '{"type":"MultiLineString","coordinates":[[[1,1],[2,1]],[[6,2],[7,2]]]}',
            ],
            'points (section dégénérée) : inchangés' => [
                'geometry' => '{"type":"MultiPoint","coordinates":[[1,1],[7,2]]}',
                'expected' => '{"type":"MultiPoint","coordinates":[[1,1],[7,2]]}',
            ],
        ];
    }

    private function provideNormalize(): array
    {
        return [
            'collection de tronçons dans le désordre : fusionnée, A à l\'ouest' => [
                'geometry' => '{"type":"GeometryCollection","geometries":[{"type":"LineString","coordinates":[[7,2],[5,2]]},{"type":"LineString","coordinates":[[1,2],[1,1]]},{"type":"LineString","coordinates":[[5,2],[1,2]]}]}',
                'expected' => '{"type":"LineString","coordinates":[[1,1],[1,2],[5,2],[7,2]]}',
            ],
            'ligne déjà orientée est-ouest : retournée pour partir de l\'ouest' => [
                'geometry' => '{"type":"LineString","coordinates":[[7,2],[5,2],[1,1]]}',
                'expected' => '{"type":"LineString","coordinates":[[1,1],[5,2],[7,2]]}',
            ],
            'morceaux disjoints : ordonnés depuis l\'extrémité ouest' => [
                'geometry' => '{"type":"MultiLineString","coordinates":[[[7,2],[6,2]],[[2,1],[1,1]]]}',
                'expected' => '{"type":"MultiLineString","coordinates":[[[1,1],[2,1]],[[6,2],[7,2]]]}',
            ],
            'points : inchangés' => [
                'geometry' => '{"type":"MultiPoint","coordinates":[[1,1],[7,2]]}',
                'expected' => '{"type":"MultiPoint","coordinates":[[1,1],[7,2]]}',
            ],
        ];
    }

    /**
     * @dataProvider provideNormalize
     */
    public function testNormalize(string $geometry, string $expected): void
    {
        $this->assertSame($expected, $this->orienter->normalize($geometry));
    }

    /**
     * @dataProvider provideOrient
     */
    public function testOrient(string $geometry, string $expected): void
    {
        $this->assertSame($expected, $this->orienter->orient($geometry, Coordinates::fromLonLat(1, 1), Coordinates::fromLonLat(7, 2)));
    }
}
