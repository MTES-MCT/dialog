<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Cifs;

use App\Application\Cifs\PolylineBuilder;
use PHPUnit\Framework\TestCase;

final class PolylineBuilderTest extends TestCase
{
    private PolylineBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PolylineBuilder();
    }

    public function testEmpty(): void
    {
        $this->assertSame([], $this->builder->build([]));
        $this->assertSame([], $this->builder->build([['48.0 2.0']]));
    }

    public function testSingleLineKeepsItsDirection(): void
    {
        $this->assertSame(
            ['48.0 2.0 48.1 2.1 48.2 2.2'],
            $this->builder->build([['48.0 2.0', '48.1 2.1', '48.2 2.2']]),
        );
    }

    public function testDisjointLinesGiveOnePolylineEach(): void
    {
        // Les deux emprises du ticket #2114, distantes de plusieurs kilomètres.
        $line1 = ['48.254442215 -3.014686584', '48.25302725 -3.013954845', '48.24834206 -3.010991824'];
        $line2 = ['48.278001167 -2.963690776', '48.276629108 -2.964337836', '48.270709216 -2.966538894'];

        $this->assertSame(
            [implode(' ', $line1), implode(' ', $line2)],
            $this->builder->build([$line1, $line2]),
        );
    }

    public function testChainedLinesGiveOnePolylineWithoutRepeatingTheJunction(): void
    {
        // A -> B puis B -> C : le point B commun n'apparaît qu'une fois.
        $this->assertSame(
            ['48.0 2.0 48.1 2.1 48.2 2.2'],
            $this->builder->build([['48.0 2.0', '48.1 2.1'], ['48.1 2.1', '48.2 2.2']]),
        );
    }

    public function testChainedLineIsReversedToFollowThePath(): void
    {
        // Le second tronçon est orienté vers la jonction : il est retourné plutôt que de sauter à son autre bout.
        $this->assertSame(
            ['48.0 2.0 48.1 2.1 48.2 2.2'],
            $this->builder->build([['48.0 2.0', '48.1 2.1'], ['48.2 2.2', '48.1 2.1']]),
        );
    }

    public function testBranchIsWalkedBackToReachTheNextOne(): void
    {
        // Un T : tronc A -> J, branches J -> B et J -> C. La polyline revient sur ses pas de B à J
        // plutôt que de sauter de B à C.
        $polylines = $this->builder->build([
            ['48.0 2.0', '48.1 2.1'], // A -> J
            ['48.1 2.1', '48.2 2.0'], // J -> B
            ['48.1 2.1', '48.2 2.2'], // J -> C
        ]);

        $this->assertSame(['48.0 2.0 48.1 2.1 48.2 2.0 48.1 2.1 48.2 2.2'], $polylines);
    }

    public function testSmallGapIsBridged(): void
    {
        // 0.0002° de latitude ≈ 22 m, sous le seuil : même tracé, une seule polyline avec les deux points du trou.
        $this->assertSame(
            ['48.0 2.0 48.1 2.1 48.1002 2.1 48.2 2.2'],
            $this->builder->build([['48.0 2.0', '48.1 2.1'], ['48.1002 2.1', '48.2 2.2']]),
        );
    }

    public function testLargeGapSplits(): void
    {
        // 0.001° de latitude ≈ 111 m, au-dessus du seuil : deux polylines.
        $this->assertSame(
            ['48.0 2.0 48.1 2.1', '48.101 2.1 48.2 2.2'],
            $this->builder->build([['48.0 2.0', '48.1 2.1'], ['48.101 2.1', '48.2 2.2']]),
        );
    }

    public function testGapToleranceIsAboutFiftyMeters(): void
    {
        // 0.0004° de latitude ≈ 44,5 m : relié ; 0.0005° ≈ 55,6 m : séparé.
        $this->assertCount(1, $this->builder->build([['48.0 2.0', '48.1 2.1'], ['48.1004 2.1', '48.2 2.2']]));
        $this->assertCount(2, $this->builder->build([['48.0 2.0', '48.1 2.1'], ['48.1005 2.1', '48.2 2.2']]));
    }

    public function testBridgesAreNeverChained(): void
    {
        // P1 (fin de A) est à 40 m de P2 (début de C) qui est à 40 m de P3 (début de B), mais P1 est à 80 m de P3.
        // Le parcours doit passer par P2 pour rejoindre B : jamais un saut de 80 m, et jamais plus de deux ponts.
        $a = ['48.0 2.0', '48.1 2.0'];      // A -> P1
        $b = ['48.1008 2.0', '48.2 2.0'];   // P3 -> B (P3 à ~89 m de P1)
        $c = ['48.1004 2.0', '48.1 2.1'];   // P2 -> C (P2 à ~44 m de P1 et de P3)

        $this->assertSame(
            ['48.0 2.0 48.1 2.0 48.1004 2.0 48.1 2.1 48.1004 2.0 48.1008 2.0 48.2 2.0'],
            $this->builder->build([$a, $b, $c]),
        );
    }

    public function testBridgeIsTakenAfterRealLines(): void
    {
        // Au nœud J arrivent un vrai tronçon (J -> B) et un pont vers C (à 30 m) : le vrai tronçon passe d'abord.
        $polylines = $this->builder->build([
            ['48.0 2.0', '48.1 2.0'],       // A -> J
            ['48.1 2.0', '48.2 2.0'],       // J -> B
            ['48.10027 2.0', '48.1 2.1'],   // C (à ~30 m de J) -> D
        ]);

        $this->assertSame(['48.0 2.0 48.1 2.0 48.2 2.0 48.1 2.0 48.10027 2.0 48.1 2.1'], $polylines);
    }

    public function testRueThomasEdison(): void
    {
        // Les 14 tronçons BD TOPO de la rue Thomas Edison (cf. PR #1661) : une seule fermeture, donc une seule
        // polyline, qui ne fait jamais de saut entre deux points consécutifs.
        $lines = [
            ['47.482195691 -1.748411265', '47.481895444 -1.748189049', '47.481760065 -1.748081246'],
            ['47.482592311 -1.748791037', '47.482527431 -1.748701466', '47.482195691 -1.748411265'],
            ['47.481760065 -1.748081246', '47.481643143 -1.747963111'],
            ['47.481643143 -1.747963111', '47.481514454 -1.747823977', '47.481349344 -1.747627069', '47.481023659 -1.747188432', '47.480682044 -1.746653938', '47.480412585 -1.746229615', '47.480348351 -1.746124145'],
            ['47.482749899 -1.749282611', '47.482729664 -1.749159756'],
            ['47.482729664 -1.749159756', '47.482709873 -1.749070199', '47.482657062 -1.748905872', '47.482592311 -1.748791037'],
            ['47.482746462 -1.749676073', '47.482739294 -1.749542406', '47.482745333 -1.749416565', '47.482749899 -1.749282611'],
            ['47.482749899 -1.749282611', '47.482772299 -1.749352445', '47.482783363 -1.749390677', '47.482794677 -1.749444894', '47.482837451 -1.749545805', '47.482851947 -1.749588332'],
            ['47.482851203 -1.749032201', '47.482885498 -1.748987357', '47.482940577 -1.748875183'],
            ['47.480120173 -1.745815231', '47.480101953 -1.745797651', '47.480028942 -1.745752594', '47.479938372 -1.74571795', '47.479841025 -1.745694677', '47.4763174 -1.746321015'],
            ['47.480348351 -1.746124145', '47.480328982 -1.7460905', '47.480320483 -1.746077774', '47.480292508 -1.746034053'],
            ['47.480292508 -1.746034053', '47.48026031 -1.745983307', '47.480207574 -1.745905463', '47.480153128 -1.745847421', '47.480120173 -1.745815231'],
            ['47.482729664 -1.749159756', '47.482767911 -1.749128566', '47.482815002 -1.749079537', '47.482851203 -1.749032201'],
        ];

        $polylines = $this->builder->build($lines);
        $this->assertCount(1, $polylines);

        // Segments du réseau d'origine, dans les deux sens.
        $segments = [];
        foreach ($lines as $line) {
            for ($i = 1; $i < \count($line); ++$i) {
                $segments[$line[$i - 1] . '|' . $line[$i]] = true;
                $segments[$line[$i] . '|' . $line[$i - 1]] = true;
            }
        }

        $coordinates = explode(' ', $polylines[0]);
        $points = [];
        for ($i = 0; $i < \count($coordinates); $i += 2) {
            $points[] = $coordinates[$i] . ' ' . $coordinates[$i + 1];
        }

        // Deux points consécutifs de la polyline sont les extrémités d'un même segment du réseau, ou à la rigueur
        // deux extrémités proches (sous la tolérance) : jamais un saut plus long. Et tous les segments sont parcourus.
        $covered = [];
        for ($i = 1; $i < \count($points); ++$i) {
            if (isset($segments[$points[$i - 1] . '|' . $points[$i]])) {
                $covered[$points[$i - 1] . '|' . $points[$i]] = true;
                $covered[$points[$i] . '|' . $points[$i - 1]] = true;
            } else {
                $this->assertLessThanOrEqual(
                    PolylineBuilder::GAP_TOLERANCE_METERS,
                    $this->distanceInMeters($points[$i - 1], $points[$i]),
                    \sprintf('Saut entre "%s" et "%s"', $points[$i - 1], $points[$i]),
                );
            }
        }
        $this->assertSame([], array_keys(array_diff_key($segments, $covered)));

        // Le retour sur ses pas est limité : au plus deux fois les segments du réseau.
        $this->assertLessThanOrEqual(\count($segments), \count($points) - 1);
    }

    private function distanceInMeters(string $a, string $b): float
    {
        [$latA, $lonA] = array_map('floatval', explode(' ', $a));
        [$latB, $lonB] = array_map('floatval', explode(' ', $b));
        $latDelta = deg2rad($latB - $latA);
        $lonDelta = deg2rad($lonB - $lonA) * cos(deg2rad(($latA + $latB) / 2));

        return 6371000 * sqrt($latDelta ** 2 + $lonDelta ** 2);
    }
}
