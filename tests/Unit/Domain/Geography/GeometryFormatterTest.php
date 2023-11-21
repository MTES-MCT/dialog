<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use App\Domain\Geography\GeometryFormatter;
use PHPUnit\Framework\TestCase;

final class GeometryFormatterTest extends TestCase
{
    public function testFormatLine(): void
    {
        $formatter = new GeometryFormatter();
        $result = $formatter->formatLine(-1.9, 43.6, 0.4, 42.3);
        $this->assertSame('LINESTRING(-1.900000 43.600000, 0.400000 42.300000)', $result);
    }
}
