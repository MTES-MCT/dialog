<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderTemplate;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationOrderTemplate = $this->createMock(RegulationOrderTemplate::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            identifier: 'F02/2023',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            otherCategoryText: null,
            regulationOrderTemplate: $regulationOrderTemplate,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrder->getUuid());
        $this->assertSame('F02/2023', $regulationOrder->getIdentifier());
        $this->assertSame(RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value, $regulationOrder->getCategory());
        $this->assertSame('Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye', $regulationOrder->getTitle());
        $this->assertEmpty($regulationOrder->getMeasures()); // Automatically set by Doctrine
        $this->assertEmpty($regulationOrder->getRegulationOrderRecord()); // Automatically set by Doctrine
        $this->assertEmpty($regulationOrder->getOtherCategoryText());
        $this->assertNull($regulationOrder->getSubject());
        $this->assertFalse($regulationOrder->isPermanent());
        $this->assertSame($regulationOrderTemplate, $regulationOrder->getRegulationOrderTemplate());
    }

    public function testUpdate(): void
    {
        $measure1 = $this->createMock(Measure::class);
        $measure2 = $this->createMock(Measure::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            identifier: 'F02/2023',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
        );

        $regulationOrder->update(
            identifier: 'F01/2023',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté temporaire',
            otherCategoryText: 'Trou en formation',
        );

        $this->assertSame('F01/2023', $regulationOrder->getIdentifier());
        $this->assertSame(RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value, $regulationOrder->getCategory());
        $this->assertSame('Arrêté temporaire', $regulationOrder->getTitle());
        $this->assertSame('Trou en formation', $regulationOrder->getOtherCategoryText());

        $regulationOrder->addMeasure($measure1);
        $regulationOrder->addMeasure($measure2); // Test duplicate
        $regulationOrder->addMeasure($measure2);

        $this->assertEquals(new ArrayCollection([$measure1, $measure2]), $regulationOrder->getMeasures());
    }
}
