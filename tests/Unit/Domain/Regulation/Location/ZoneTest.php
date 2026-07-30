<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\Zone;
use PHPUnit\Framework\TestCase;

final class ZoneTest extends TestCase
{
    public function testGetters(): void
    {
        $location = $this->createMock(Location::class);

        $zone = new Zone(
            uuid: 'uuid',
            location: $location,
            label: 'Centre-ville',
            geometry: '<polygon>',
        );

        $this->assertSame('uuid', $zone->getUuid());
        $this->assertSame($location, $zone->getLocation());
        $this->assertSame('Centre-ville', $zone->getLabel());
        $this->assertSame('<polygon>', $zone->getGeometry());
    }

    public function testUpdate(): void
    {
        $zone = new Zone(
            uuid: 'uuid',
            location: $this->createMock(Location::class),
            label: 'Centre-ville',
            geometry: '<polygon>',
        );

        $zone->update('Quartier de la gare', '<polygon-new>');

        $this->assertSame('Quartier de la gare', $zone->getLabel());
        $this->assertSame('<polygon-new>', $zone->getGeometry());
    }
}
