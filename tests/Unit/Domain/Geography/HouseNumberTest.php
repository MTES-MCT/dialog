<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use App\Domain\Geography\Exception\InvalidHouseNumberException;
use App\Domain\Geography\HouseNumber;
use PHPUnit\Framework\TestCase;

final class HouseNumberTest extends TestCase
{
    public function testCompare(): void
    {
        $this->assertTrue(HouseNumber::compare('1', '1'));
        $this->assertTrue(HouseNumber::compare('1', '2'));
        $this->assertTrue(HouseNumber::compare('1', '1bis'));
        $this->assertTrue(HouseNumber::compare('1', '2ter'));
        $this->assertTrue(HouseNumber::compare('1bis', '1'));
        $this->assertFalse(HouseNumber::compare('2', '1'));
        $this->assertFalse(HouseNumber::compare('2bis', '1'));
    }

    public function testCompareInvalidLeft(): void
    {
        $this->expectException(InvalidHouseNumberException::class);
        $this->expectExceptionMessage('left is not a valid house number: abc');
        HouseNumber::compare('abc', '1');
    }

    public function testCompareInvalidRight(): void
    {
        $this->expectException(InvalidHouseNumberException::class);
        $this->expectExceptionMessage('right is not a valid house number: abc');
        HouseNumber::compare('1', 'abc');
    }
}
