<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            identifier: 'F02/2023',
            category: 'category_evenement',
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            startDate: $start,
            endDate: $end,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrder->getUuid());
        $this->assertSame('F02/2023', $regulationOrder->getIdentifier());
        $this->assertSame('category_evenement', $regulationOrder->getCategory());
        $this->assertSame('Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye', $regulationOrder->getDescription());
        $this->assertSame($start, $regulationOrder->getStartDate());
        $this->assertSame($end, $regulationOrder->getEndDate());
        $this->assertEmpty($regulationOrder->getLocations()); // Automatically set by Doctrine
    }

    public function testUpdate(): void
    {
        $start = new \DateTime('2023-03-13');
        $newStart = new \DateTime('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $regulationOrder = new RegulationOrder(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            identifier: 'F02/2023',
            category: 'category_evenement',
            description: 'Arrêté temporaire portant réglementation de la circulation sur : Routes Départementales N° 3-93, Voie communautaire de la Colleraye',
            startDate: $start,
            endDate: null,
        );

        $regulationOrder->update(
            identifier: 'F01/2023',
            category: 'category_travaux',
            description: 'Arrêté temporaire',
            startDate: $newStart,
            endDate: $end,
        );

        $this->assertSame('F01/2023', $regulationOrder->getIdentifier());
        $this->assertSame('category_travaux', $regulationOrder->getCategory());
        $this->assertSame('Arrêté temporaire', $regulationOrder->getDescription());
        $this->assertSame($newStart, $regulationOrder->getStartDate());
        $this->assertSame($end, $regulationOrder->getEndDate());
    }
}
