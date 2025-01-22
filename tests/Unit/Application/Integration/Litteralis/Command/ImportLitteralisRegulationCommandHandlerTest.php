<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Integration\Litteralis\Command;

use App\Application\CommandBusInterface;
use App\Application\Integration\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\Integration\Litteralis\Command\ImportLitteralisRegulationCommandHandler;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class ImportLitteralisRegulationCommandHandlerTest extends TestCase
{
    private $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testImport(): void
    {
        $generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $measureCommand = $this->createMock(SaveMeasureCommand::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $measure = $this->createMock(Measure::class);

        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $regulationOrder
            ->expects(self::once())
            ->method('addMeasure')
            ->with($measure);

        $publishCommand = new PublishRegulationCommand($regulationOrderRecord);

        $matcher = self::exactly(3);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($generalInfoCommand, $command) ?: $regulationOrderRecord,
                    2 => $this->assertEquals($measureCommand, $command) ?: $measure,
                    3 => $this->assertEquals($publishCommand, $command),
                },
            );

        $handler = new ImportLitteralisRegulationCommandHandler($this->commandBus);
        $command = new ImportLitteralisRegulationCommand($generalInfoCommand, [$measureCommand]);

        $this->assertEmpty($handler($command));
    }
}
