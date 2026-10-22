<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\RegulationMeasureLegendBuilder;
use PHPUnit\Framework\TestCase;

final class RegulationMeasureLegendBuilderTest extends TestCase
{
    public function testBuildSwatches(): void
    {
        $builder = new RegulationMeasureLegendBuilder();

        $swatches = $builder->buildSwatches();

        $expectedTypes = [
            'noEntry',
            'speedLimitation',
            'parkingProhibited',
            'alternateRoad',
            'noOvertaking',
        ];

        $this->assertSame($expectedTypes, array_keys($swatches));

        foreach ($swatches as $type => $swatch) {
            $this->assertStringStartsWith('data:image/png;base64,', $swatch, $type);

            $base64 = substr($swatch, \strlen('data:image/png;base64,'));
            $bytes = base64_decode($base64, true);

            $this->assertNotFalse($bytes, $type);
            $this->assertNotEmpty($bytes, $type);

            $size = getimagesizefromstring((string) $bytes);
            $this->assertNotFalse($size, $type);
            $this->assertSame(80, $size[0], $type);
            $this->assertSame(8, $size[1], $type);
            $this->assertSame('image/png', $size['mime'], $type);
        }
    }
}
