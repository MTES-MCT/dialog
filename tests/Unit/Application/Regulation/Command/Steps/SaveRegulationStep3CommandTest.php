<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

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

        $this->assertEmpty($command->startPeriod);
        $this->assertEmpty($command->endPeriod);
    }

    public function testWithOverallPeriod(): void
    {
        $start = new \DateTime('2022-01-11');
        $end = new \DateTime('2022-01-12');

        $overallPeriod = $this->createMock(OverallPeriod::class);
        $overallPeriod
            ->expects(self::once())
            ->method('getStartPeriod')
            ->willReturn($start);
        $overallPeriod
            ->expects(self::once())
            ->method('getEndPeriod')
            ->willReturn($end);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationStep3Command::create($regulationOrderRecord, $overallPeriod);

        $this->assertSame($command->startPeriod, $start);
        $this->assertSame($command->endPeriod, $end);
    }
}
