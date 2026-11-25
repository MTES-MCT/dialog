<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Integration\JOP\Command;

use App\Application\CommandBusInterface;
use App\Application\Integration\JOP\Command\ImportJOPRegulationCommand;
use App\Application\Integration\JOP\Command\ImportJOPRegulationCommandHandler;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class ImportJOPRegulationCommandHandlerTest extends TestCase
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
            ->method('handle')->willReturnCallback(function (...$parameters) use ($matcher, $generalInfoCommand, $measureCommand, $publishCommand, $regulationOrderRecord, $measure) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($generalInfoCommand, $parameters[0]);

                    return $regulationOrderRecord;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($measureCommand, $parameters[0]);

                    return $measure;
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertEquals($publishCommand, $parameters[0]);

                    return null;
                }
            });

        $handler = new ImportJOPRegulationCommandHandler($this->commandBus);
        $command = new ImportJOPRegulationCommand($generalInfoCommand, [$measureCommand]);
        $this->assertSame(RegulationOrderRecordSourceEnum::JOP->value, $generalInfoCommand->source);

        $this->assertEmpty($handler($command));
    }
}
