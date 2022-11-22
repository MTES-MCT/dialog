<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder;

use App\Domain\RegulationOrder\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $createdAt = new \DateTimeImmutable('2022-11-22');
        $regulationOrder = new RegulationOrder(
            '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            'Commune de Savenay',
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrder->getUuid());
        $this->assertSame('Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye', $regulationOrder->getDescription());
        $this->assertSame('Commune de Savenay', $regulationOrder->getIssuingAuthority());
    }
}
