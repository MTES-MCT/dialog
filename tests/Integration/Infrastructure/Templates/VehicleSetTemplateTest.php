<?php

declare(strict_types=1);

namespace App\Test\Integration\Infrastructure\Templates;

use App\Application\Regulation\View\VehicleSetView;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Measure;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VehicleSetTemplateTest extends KernelTestCase
{
    // New line management in the template is fiddly, so we have this dedicated test to ensure the template is rendered correctly.
    public function testRender(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var \Twig\Environment */
        $twig = $container->get(\Twig\Environment::class);

        $measure = $this->createMock(Measure::class);

        $context = [
            'vehicleSet' => VehicleSetView::fromEntity(
                new VehicleSet(
                    '065a50b7-4991-71cd-8000-0177fbebc108',
                    measure: $measure,
                    restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::DIMENSIONS->value],
                    exemptedTypes: [VehicleTypeEnum::EMERGENCY_SERVICES->value],
                    heavyweightMaxWeight: 3.5,
                    maxWidth: 4,
                    maxHeight: 2.4,
                ),
            ),
        ];

        $text = $twig->render('regulation/_vehicle_set.html.twig', $context);

        $this->assertSame(
            'pour les véhicules de plus de 3,5 tonnes, 4 mètres de large ou 2,4 mètres de haut, sauf véhicules d&#039;urgence',
            $text,
        );
    }
}
