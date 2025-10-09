<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Mapper\Transformers;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use PHPUnit\Framework\TestCase;

final class EnumTransformersTest extends TestCase
{
    public function testToStringReturnsNullWhenValueIsNull(): void
    {
        self::assertNull(EnumTransformers::toString(null));
    }

    public function testToStringReturnsStringValueForBackedEnum(): void
    {
        self::assertSame('lane', EnumTransformers::toString(RoadTypeEnum::LANE));
        self::assertSame('departmentalRoad', EnumTransformers::toString(RoadTypeEnum::DEPARTMENTAL_ROAD));
        self::assertSame('BOTH', EnumTransformers::toString(DirectionEnum::BOTH));
        self::assertSame('A_TO_B', EnumTransformers::toString(DirectionEnum::A_TO_B));
    }
}
