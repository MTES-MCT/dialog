<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use App\Domain\Geography\GeometryFormatter;
use PHPUnit\Framework\TestCase;

final class GeometryFormatterTest extends TestCase
{
    public function testFormatPoint(): void
    {
        $formatter = new GeometryFormatter();
        $point = $formatter->formatPoint(43.6, -1.9);
        $this->assertSame('POINT(-1.900000 43.600000)', $point);
    }
}
