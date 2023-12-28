<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\EudonetParis\Command;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommandHandler;
use App\Application\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Location;
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
        $locationCommand = $this->createMock(SaveRegulationLocationCommand::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $location = $this->createMock(Location::class);

        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $regulationOrder
            ->expects(self::once())
            ->method('addLocation')
            ->with($location);

        $publishCommand = new PublishRegulationCommand($regulationOrderRecord);

        $matcher = self::exactly(3);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($generalInfoCommand, $command) ?: $regulationOrderRecord,
                    2 => $this->assertEquals($locationCommand, $command) ?: $location,
                    3 => $this->assertEquals($publishCommand, $command),
                },
            );

        $handler = new ImportEudonetParisRegulationCommandHandler($this->commandBus);
        $command = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$locationCommand]);

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
}
