<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\BacIdf\Command;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\BacIdf\Command\ImportBacIdfRegulationCommandHandler;
use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class ImportBacIdfRegulationCommandHandlerTest extends TestCase
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

        $handler = new ImportBacIdfRegulationCommandHandler($this->commandBus);
        $command = new ImportBacIdfRegulationCommand($generalInfoCommand, [$locationCommand]);

        $this->assertEmpty($handler($command));
    }

    public function testErrorCannotBePublished(): void
    {
        $this->expectException(ImportBacIdfRegulationFailedException::class);

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

        $handler = new ImportBacIdfRegulationCommandHandler($this->commandBus);
        $command = new ImportBacIdfRegulationCommand($generalInfoCommand, []);

        $handler($command);
    }

    public function testErrorGeocodingFailure(): void
    {
        $this->expectException(ImportBacIdfRegulationFailedException::class);

        $generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $locationCommand = $this->createMock(SaveRegulationLocationCommand::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($generalInfoCommand, $command) ?: $regulationOrderRecord,
                    2 => $this->assertEquals($locationCommand, $command) ?: throw new GeocodingFailureException('Could not geocode'),
                },
            );

        $handler = new ImportBacIdfRegulationCommandHandler($this->commandBus);
        $command = new ImportBacIdfRegulationCommand($generalInfoCommand, [$locationCommand]);

        $handler($command);
    }
}
