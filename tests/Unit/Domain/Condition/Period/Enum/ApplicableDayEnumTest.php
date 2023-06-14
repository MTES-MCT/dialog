<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period\Enum;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use PHPUnit\Framework\TestCase;

final class ApplicableDayEnumTest extends TestCase
{
    public function testEnum(): void
    {
        $this->assertSame(ApplicableDayEnum::getValues(), [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ]);

        $this->assertSame(ApplicableDayEnum::getDayIndex('monday'), 0);
        $this->assertSame(ApplicableDayEnum::getDayIndex('wednesday'), 2);
        $this->assertSame(ApplicableDayEnum::getDayIndex('sunday'), 6);
        $this->assertNull(ApplicableDayEnum::getDayIndex('wtf'));
    }
}
