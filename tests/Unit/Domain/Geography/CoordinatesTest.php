<?php

declare(strict_types=1);

namespace App\Tests\Domain\Geography;

use PHPUnit\Framework\TestCase;
use App\Domain\Geography\Coordinates;

final class CoordinatesTest extends TestCase
{
    public function testGetters(): void
    {
        $coords = Coordinates::fromLatLon(43.6, -1.9);
        $this->assertSame(43.6, $coords->getLatitude());
        $this->assertSame(-1.9, $coords->getLongitude());
    }
}
