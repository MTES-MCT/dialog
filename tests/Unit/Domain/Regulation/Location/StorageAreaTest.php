<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Location\StorageArea;
use PHPUnit\Framework\TestCase;

final class StorageAreaTest extends TestCase
{
    public function testGetters(): void
    {
        $storageArea = new StorageArea(
            uuid: 'b4812143-c4d8-44e6-8c3a-34688becae6e',
            sourceId: 'Pl-5',
            description: 'La Chapelle du Mont de France _ 42-71-N79-51-1 _ capacité 30 - située sur BAU  _ District Mâcon _ CEI CHARANY LES MACON',
            administrator: 'DIR Centre-Est',
            roadNumber: 'N79',
            fromPointNumber: '50',
            fromSide: 'D',
            fromAbscissa: 600,
            toPointNumber: '51',
            toSide: 'D',
            toAbscissa: 150,
            geometry: '<geometry>',
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $storageArea->getUuid());
        $this->assertSame('Pl-5', $storageArea->getSourceId());
        $this->assertSame('La Chapelle du Mont de France _ 42-71-N79-51-1 _ capacité 30 - située sur BAU  _ District Mâcon _ CEI CHARANY LES MACON', $storageArea->getDescription());
        $this->assertSame('DIR Centre-Est', $storageArea->getAdministrator());
        $this->assertSame('N79', $storageArea->getRoadNumber());
        $this->assertSame('50', $storageArea->getFromPointNumber());
        $this->assertSame('D', $storageArea->getFromSide());
        $this->assertSame(600, $storageArea->getFromAbscissa());
        $this->assertSame('51', $storageArea->getToPointNumber());
        $this->assertSame(150, $storageArea->getToAbscissa());
        $this->assertSame('D', $storageArea->getToSide());
        $this->assertSame('<geometry>', $storageArea->getGeometry());
    }
}
