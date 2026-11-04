<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Enum;

use App\Domain\Regulation\Enum\DirectionEnum;
use PHPUnit\Framework\TestCase;

final class DirectionEnumTest extends TestCase
{
    public function testToCifsDirection(): void
    {
        $this->assertSame('BOTH_DIRECTIONS', DirectionEnum::toCifsDirection(DirectionEnum::BOTH->value));
        $this->assertSame('ONE_DIRECTION', DirectionEnum::toCifsDirection(DirectionEnum::A_TO_B->value));
        $this->assertSame('ONE_DIRECTION', DirectionEnum::toCifsDirection(DirectionEnum::B_TO_A->value));
    }

    public function testToDatexDirection(): void
    {
        $this->assertSame('both', DirectionEnum::toDatexDirection(DirectionEnum::BOTH->value));
        $this->assertSame('aligned', DirectionEnum::toDatexDirection(DirectionEnum::A_TO_B->value));
        $this->assertSame('opposite', DirectionEnum::toDatexDirection(DirectionEnum::B_TO_A->value));
        $this->assertNull(DirectionEnum::toDatexDirection(null));
        $this->assertNull(DirectionEnum::toDatexDirection(''));
    }
}
