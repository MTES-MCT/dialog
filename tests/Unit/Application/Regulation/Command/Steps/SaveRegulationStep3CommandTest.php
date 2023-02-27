<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep3CommandTest extends TestCase
{
    public function testWithoutOverallPeriod(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationStep3Command::create($regulationOrderRecord);

        $this->assertEmpty($command->startDate);
        $this->assertEmpty($command->startTime);
        $this->assertEmpty($command->endDate);
        $this->assertEmpty($command->endTime);
    }

    public function testWithOverallPeriod(): void
    {
        $startDate = new \DateTime('2022-01-11');
        $startTime = new \DateTime('08:00:00');
        $endDate = new \DateTime('2022-01-12');
        $endTime = new \DateTime('01:00:00');

        $overallPeriod = $this->createMock(OverallPeriod::class);
        $overallPeriod
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate);
        $overallPeriod
            ->expects(self::once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $overallPeriod
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate);
        $overallPeriod
            ->expects(self::once())
            ->method('getEndTime')
            ->willReturn($endTime);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationStep3Command::create($regulationOrderRecord, $overallPeriod);

        $this->assertSame($command->startDate, $startDate);
        $this->assertSame($command->startTime, $startTime);
        $this->assertSame($command->endDate, $endDate);
        $this->assertSame($command->endTime, $endTime);
    }
}
