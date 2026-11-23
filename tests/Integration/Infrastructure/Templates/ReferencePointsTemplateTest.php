<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Templates;

use App\Application\Regulation\View\Measure\NumberedRoadView;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReferencePointsTemplateTest extends KernelTestCase
{
    private function provideTestRender(): array
    {
        return [
            'no-direction' => [
                'direction' => null,
                'result' => 'du PR 1+0 (côté U) au PR 4+250 (côté D)',
            ],
            'both' => [
                'direction' => 'BOTH',
                'result' => 'du PR 1+0 (côté U) au PR 4+250 (côté D)',
            ],
            'a-to-b' => [
                'direction' => 'A_TO_B',
                'result' => 'du PR 1+0 (côté U) vers le PR 4+250 (côté D)',
            ],
            // Sens B vers A : les PR sont lus dans le sens de circulation
            'b-to-a' => [
                'direction' => 'B_TO_A',
                'result' => 'du PR 4+250 (côté D) vers le PR 1+0 (côté U)',
            ],
        ];
    }

    /**
     * @dataProvider provideTestRender
     */
    public function testRender(?string $direction, string $result): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $twig = $container->get(\Twig\Environment::class);

        $location = new NumberedRoadView(
            administrator: 'Ardennes',
            roadNumber: 'D322',
            fromPointNumber: '1',
            fromAbscissa: 0,
            fromSide: 'U',
            toPointNumber: '4',
            toAbscissa: 250,
            toSide: 'D',
            direction: $direction,
        );

        $this->assertSame($result, $twig->render('regulation/_reference_points.html.twig', ['location' => $location]));
    }

    public function testRenderWithoutPointNumbers(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $twig = $container->get(\Twig\Environment::class);

        $location = new NumberedRoadView(
            administrator: 'Ardennes',
            roadNumber: 'D322',
            fromPointNumber: null,
            fromAbscissa: 0,
            fromSide: null,
            toPointNumber: null,
            toAbscissa: 0,
            toSide: null,
            direction: 'A_TO_B',
        );

        $this->assertSame('', $twig->render('regulation/_reference_points.html.twig', ['location' => $location]));
    }
}
