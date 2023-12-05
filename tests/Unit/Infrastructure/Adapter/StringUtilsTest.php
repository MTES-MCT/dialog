<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\StringUtils;
use PHPUnit\Framework\TestCase;

final class StringUtilsTest extends TestCase
{
    public function testNormalizeEmail(): void
    {
        $stringUtils = new StringUtils();

        $this->assertEquals('mathieu@fairness.coop', $stringUtils->normalizeEmail('  mathieU@faIRNess.coop  '));
    }

    public function testKebabCase(): void
    {
        $stringUtils = new StringUtils();

        $this->assertEquals('test-valeur', $stringUtils->toKebabCase('TestValeur'));
    }
}
