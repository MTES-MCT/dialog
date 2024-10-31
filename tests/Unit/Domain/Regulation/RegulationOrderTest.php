<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Organization\VisaModel\VisaModel;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $visaModel = $this->createMock(VisaModel::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            identifier: 'F02/2023',
            category: RegulationOrderCategoryEnum::EVENT->value,
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            otherCategoryText: null,
            visaModel: $visaModel,
            additionalVisas: ['vu que 1'],
            additionalReasons: ['considérant que'],
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrder->getUuid());
        $this->assertSame('F02/2023', $regulationOrder->getIdentifier());
        $this->assertSame(RegulationOrderCategoryEnum::EVENT->value, $regulationOrder->getCategory());
        $this->assertSame('Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye', $regulationOrder->getDescription());
        $this->assertEmpty($regulationOrder->getMeasures()); // Automatically set by Doctrine
        $this->assertEmpty($regulationOrder->getRegulationOrderRecord()); // Automatically set by Doctrine
        $this->assertEmpty($regulationOrder->getOtherCategoryText());
        $this->assertFalse($regulationOrder->isPermanent());
        $this->assertSame($visaModel, $regulationOrder->getVisaModel());
        $this->assertSame(['vu que 1'], $regulationOrder->getAdditionalVisas());
        $this->assertSame(['considérant que'], $regulationOrder->getAdditionalReasons());
    }

    public function testUpdate(): void
    {
        $measure1 = $this->createMock(Measure::class);
        $measure2 = $this->createMock(Measure::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            identifier: 'F02/2023',
            category: RegulationOrderCategoryEnum::EVENT->value,
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
        );

        $regulationOrder->update(
            identifier: 'F01/2023',
            category: RegulationOrderCategoryEnum::OTHER->value,
            description: 'Arrêté temporaire',
            otherCategoryText: 'Trou en formation',
        );

        $this->assertSame('F01/2023', $regulationOrder->getIdentifier());
        $this->assertSame(RegulationOrderCategoryEnum::OTHER->value, $regulationOrder->getCategory());
        $this->assertSame('Arrêté temporaire', $regulationOrder->getDescription());
        $this->assertSame('Trou en formation', $regulationOrder->getOtherCategoryText());

        $regulationOrder->addMeasure($measure1);
        $regulationOrder->addMeasure($measure2); // Test duplicate
        $regulationOrder->addMeasure($measure2);

        $this->assertEquals(new ArrayCollection([$measure1, $measure2]), $regulationOrder->getMeasures());
    }
}
