<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation;

use App\Domain\Condition\RegulationCondition;
use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            issuingAuthority: 'Commune de Savenay',
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            regulationCondition: $regulationCondition,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrder->getUuid());
        $this->assertSame('Commune de Savenay', $regulationOrder->getIssuingAuthority());
        $this->assertSame('Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye', $regulationOrder->getDescription());
        $this->assertSame($regulationCondition, $regulationOrder->getRegulationCondition());
    }

    public function testUpdate(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            issuingAuthority: 'Commune de Savenay',
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            regulationCondition: $regulationCondition,
        );

        $regulationOrder->update(
            issuingAuthority: 'Commune de Paris',
            description: 'Arrêté temporaire',
        );

        $this->assertSame('Commune de Paris', $regulationOrder->getIssuingAuthority());
        $this->assertSame('Arrêté temporaire', $regulationOrder->getDescription());
    }
}
