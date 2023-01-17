<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Infrastructure\Adapter\DoctrinePostGISGeographyFormatter;
use PHPUnit\Framework\TestCase;

final class DoctrinePostGISGeographyFormatterTest extends TestCase
{
    public function testFormatPoint(): void
    {
        $formatter = new DoctrinePostGISGeographyFormatter();
        $point = $formatter->formatPoint(43.6, -1.9);
        $this->assertSame('POINT(43.600000 -1.900000)', $point);
    }
}
