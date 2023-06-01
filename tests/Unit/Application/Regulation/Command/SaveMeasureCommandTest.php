<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\Condition\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Condition\Condition;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class SaveMeasureCommandTest extends TestCase
{
    public function testWithoutMeasure(): void
    {
        $command = new SaveMeasureCommand();

        $this->assertEmpty($command->measure);
        $this->assertEmpty($command->type);
        $this->assertEmpty($command->location);
        $this->assertEmpty($command->periods);
    }

    public function testWithMeasure(): void
    {
        $location = $this->createMock(Location::class);
        $condition = $this->createMock(Condition::class);
        $period = $this->createMock(Period::class);
        $measure = $this->createMock(Measure::class);

        $condition
            ->expects(self::exactly(2))
            ->method('getPeriod')
            ->willReturn($period);

        $measure
            ->expects(self::once())
            ->method('getLocation')
            ->willReturn($location);

        $measure
            ->expects(self::once())
            ->method('getConditions')
            ->willReturn([$condition]);

        $measure
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::ALTERNATE_ROAD->value);

        $command = new SaveMeasureCommand($measure);

        $this->assertSame($measure, $command->measure);
        $this->assertSame(MeasureTypeEnum::ALTERNATE_ROAD->value, $command->type);
        $this->assertSame($location, $command->location);
        $this->assertEquals([new SavePeriodCommand($period)], $command->periods);
    }
}
