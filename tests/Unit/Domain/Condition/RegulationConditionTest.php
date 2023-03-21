<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition;

use App\Domain\Condition\RegulationCondition;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class RegulationConditionTest extends TestCase
{
    public function testGetters(): void
    {
        $measure = $this->createMock(Measure::class);

        $regulationCondition = new RegulationCondition(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            false,
            $measure,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $regulationCondition->getUuid());
        $this->assertSame(false, $regulationCondition->isNegate());
        $this->assertSame($measure, $regulationCondition->getMeasure());
        $this->assertSame(null, $regulationCondition->getVehicleCharacteristics()); // Automatically set by Doctrine
        $this->assertSame(null, $regulationCondition->getPeriod()); // Automatically set by Doctrine
    }
}
