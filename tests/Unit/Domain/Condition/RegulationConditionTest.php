<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition;

use App\Domain\Condition\Condition;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class ConditionTest extends TestCase
{
    public function testGetters(): void
    {
        $measure = $this->createMock(Measure::class);

        $condition = new Condition(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            false,
            $measure,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $condition->getUuid());
        $this->assertSame(false, $condition->isNegate());
        $this->assertSame($measure, $condition->getMeasure());
        $this->assertSame(null, $condition->getVehicleCharacteristics()); // Automatically set by Doctrine
        $this->assertSame(null, $condition->getPeriod()); // Automatically set by Doctrine
    }
}
