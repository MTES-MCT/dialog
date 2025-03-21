<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Integration\EudonetParis\Command;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Integration\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Integration\EudonetParis\Command\ImportEudonetParisRegulationCommandHandler;
use App\Application\Integration\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class ImportEudonetParisRegulationCommandHandlerTest extends TestCase
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

        $handler = new ImportEudonetParisRegulationCommandHandler($this->commandBus);
        $command = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$measureCommand]);

        $this->assertEmpty($handler($command));
    }

    public function testErrorCannotBePublished(): void
    {
        $this->expectException(ImportEudonetParisRegulationFailedException::class);

        $generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $publishCommand = new PublishRegulationCommand($regulationOrderRecord);

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($generalInfoCommand, $command) ?: $regulationOrderRecord,
                    2 => $this->assertEquals($publishCommand, $command) ?: throw new RegulationOrderRecordCannotBePublishedException(),
                },
            );

        $handler = new ImportEudonetParisRegulationCommandHandler($this->commandBus);
        $command = new ImportEudonetParisRegulationCommand($generalInfoCommand, []);

        $handler($command);
    }

    public function testErrorGeocodingFailure(): void
    {
        $this->expectException(ImportEudonetParisRegulationFailedException::class);

        $generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $measureCommand = $this->createMock(SaveMeasureCommand::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($generalInfoCommand, $command) ?: $regulationOrderRecord,
                    2 => $this->assertEquals($measureCommand, $command) ?: throw new GeocodingFailureException('Could not geocode'),
                },
            );

        $handler = new ImportEudonetParisRegulationCommandHandler($this->commandBus);
        $command = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$measureCommand]);

        $handler($command);
    }
}
