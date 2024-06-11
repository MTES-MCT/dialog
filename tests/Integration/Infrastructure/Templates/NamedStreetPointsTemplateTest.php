<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Templates;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NamedStreetPointsTemplateTest extends KernelTestCase
{
    private function provideTestRender(): array
    {
        return [
            'start:none-end:none' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => null,
                        'toHouseNumber' => null,
                        'toRoadName' => null,
                    ],
                ],
                'result' => '',
            ],
            'start:houseNumber-end:none' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => null,
                        'toRoadName' => null,
                    ],
                ],
                'result' => 'à partir du n° 1',
            ],
            'start:roadName-end:none' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => 'Rue Test',
                        'toHouseNumber' => null,
                        'toRoadName' => null,
                    ],
                ],
                'result' => 'à partir de Rue Test',
            ],
            'start:none-end:houseNumber' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => null,
                        'toHouseNumber' => '1',
                        'toRoadName' => null,
                    ],
                ],
                'result' => 'jusqu&#039;au n° 1',
            ],
            'start:none-end:roadName' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => null,
                        'toHouseNumber' => null,
                        'toRoadName' => 'Rue Test',
                    ],
                ],
                'result' => 'jusqu&#039;à Rue Test',
            ],
            'start:houseNumber-end:houseNumber' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => '10',
                        'toRoadName' => null,
                    ],
                ],
                'result' => 'du n° 1 au n° 10',
            ],
            'start:houseNumber-end:roadName' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => null,
                        'toRoadName' => 'Rue Test',
                    ],
                ],
                'result' => 'du n° 1 à Rue Test',
            ],
            'start:roadName-end:houseNumber' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => 'Rue Test',
                        'toHouseNumber' => '10',
                        'toRoadName' => null,
                    ],
                ],
                'result' => 'de Rue Test au n° 10',
            ],
            'start:roadName-end:roadName' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => 'Rue Test',
                        'toHouseNumber' => null,
                        'toRoadName' => 'Boulevard Test',
                    ],
                ],
                'result' => 'de Rue Test à Boulevard Test',
            ],
        ];
    }

    /**
     * @dataProvider provideTestRender
     */
    public function testRender(array $context, string $result): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $twig = $container->get(\Twig\Environment::class);

        $this->assertSame($result, $twig->render('location/named_street/points.html.twig', $context));
    }
}
