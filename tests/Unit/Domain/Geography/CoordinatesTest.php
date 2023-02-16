<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use PHPUnit\Framework\TestCase;
use App\Domain\Geography\Coordinates;

final class CoordinatesTest extends TestCase
{
    public function testGetters(): void
    {
        $coords = Coordinates::fromLonLat(-1.9, 43.6);
        $this->assertSame(-1.9, $coords->longitude);
        $this->assertSame(43.6, $coords->latitude);
    }
}
