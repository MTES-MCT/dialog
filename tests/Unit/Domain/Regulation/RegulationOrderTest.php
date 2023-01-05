<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation;

use App\Domain\Condition\RegulationCondition;
use App\Domain\Regulation\Enum\RegulationOrderImpactEnum;
use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            issuingAuthority: 'Commune de Savenay',
            regulationCondition: $regulationCondition,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrder->getUuid());
        $this->assertSame('Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye', $regulationOrder->getDescription());
        $this->assertSame('Commune de Savenay', $regulationOrder->getIssuingAuthority());
        $this->assertSame($regulationCondition, $regulationOrder->getRegulationCondition());
    }
}
