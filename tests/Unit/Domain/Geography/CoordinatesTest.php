<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use App\Domain\Geography\Coordinates;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
    public function testGetters(): void
    {
        $coords = Coordinates::fromLonLat(-1.9, 43.6);
        $this->assertSame(-1.9, $coords->longitude);
        $this->assertSame(43.6, $coords->latitude);
    }
}
