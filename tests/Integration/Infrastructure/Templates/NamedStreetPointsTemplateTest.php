<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class NamedStreetPointsTemplateTest extends KernelTestCase
{
    public static function provideTestRender(): array
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
            'direction:both' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => '10',
                        'toRoadName' => null,
                        'direction' => 'BOTH',
                    ],
                ],
                'result' => 'du n° 1 au n° 10',
            ],
            'direction:aToB-houseNumbers' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => '10',
                        'toRoadName' => null,
                        'direction' => 'A_TO_B',
                    ],
                ],
                'result' => 'du n° 1 vers le n° 10',
            ],
            'direction:bToA-houseNumbers' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => '10',
                        'toRoadName' => null,
                        'direction' => 'B_TO_A',
                    ],
                ],
                'result' => 'du n° 10 vers le n° 1',
            ],
            'direction:aToB-roadNames' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => 'Rue Test',
                        'toHouseNumber' => null,
                        'toRoadName' => 'Boulevard Test',
                        'direction' => 'A_TO_B',
                    ],
                ],
                'result' => 'de Rue Test vers Boulevard Test',
            ],
            'direction:bToA-houseNumber-roadName' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => null,
                        'toRoadName' => 'Rue Test',
                        'direction' => 'B_TO_A',
                    ],
                ],
                'result' => 'de Rue Test vers le n° 1',
            ],
            // Une seule extrémité : le libellé ne peut pas porter le sens, il reste inchangé
            'direction:aToB-start-only' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => '1',
                        'fromRoadName' => null,
                        'toHouseNumber' => null,
                        'toRoadName' => null,
                        'direction' => 'A_TO_B',
                    ],
                ],
                'result' => 'à partir du n° 1',
            ],
            'direction:bToA-end-only' => [
                'context' => [
                    'location' => [
                        'fromHouseNumber' => null,
                        'fromRoadName' => null,
                        'toHouseNumber' => '1',
                        'toRoadName' => null,
                        'direction' => 'B_TO_A',
                    ],
                ],
                'result' => 'jusqu&#039;au n° 1',
            ],
        ];
    }

    #[DataProvider('provideTestRender')]
    public function testRender(array $context, string $result): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $twig = $container->get(Environment::class);

        $this->assertSame($result, $twig->render('location/named_street/points.html.twig', $context));
    }
}
