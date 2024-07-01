<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\RawGeoJSON;
use PHPUnit\Framework\TestCase;

final class RawGeoJSONTest extends TestCase
{
    public function testGetters(): void
    {
        $location = $this->createMock(Location::class);

        $rawGeoJSON = new RawGeoJSON(
            uuid: 'b4812143-c4d8-44e6-8c3a-34688becae6e',
            location: $location,
            label: 'Evénement spécial',
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $rawGeoJSON->getUuid());
        $this->assertSame($location, $rawGeoJSON->getLocation());
        $this->assertSame('Evénement spécial', $rawGeoJSON->getLabel());

        $newLabel = 'Evénement très spécial';

        $rawGeoJSON->update(
            $newLabel,
        );

        $this->assertSame($newLabel, $rawGeoJSON->getLabel());
    }
}
