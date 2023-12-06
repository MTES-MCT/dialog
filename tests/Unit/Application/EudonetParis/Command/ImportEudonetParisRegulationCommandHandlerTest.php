<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\EudonetParis\Command;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommandHandler;
use App\Application\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\EudonetParis\EudonetParisLocationItem;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
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
        $measureCommand = $this->createMock(SaveMeasureCommand::class);

        $locationItem = new EudonetParisLocationItem();
        $locationItem->roadName = 'Rue Eugène Berthoud';
        $locationItem->fromHouseNumber = '15';
        $locationItem->toHouseNumber = '26';
        $locationItem->measures = [$measureCommand];

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

        $locationCommand = new SaveRegulationLocationCommand($regulationOrderRecord);
        $locationCommand->cityCode = '75056';
        $locationCommand->cityLabel = 'Paris';
        $locationCommand->roadName = 'Rue Eugène Berthoud';
        $locationCommand->fromHouseNumber = '15';
        $locationCommand->toHouseNumber = '26';
        $locationCommand->geometry = null;
        $locationCommand->measures = [$measureCommand];

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
        $command = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$locationItem]);

        $this->assertEmpty($handler($command));
    }

    public function testImportWithJunction(): void
    {
        $junctionGeometry = GeoJSON::toLineString([
            // Rue Jean Perrin -> Rue Adrien Lesesne
            Coordinates::fromLonLat(2.3453101, 48.9062362),
            Coordinates::fromLonLat(2.34944, 48.9045598),
        ]);

        $generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $measureCommand = $this->createMock(SaveMeasureCommand::class);

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;

        $locationItem = new EudonetParisLocationItem();
        $locationItem->roadName = 'Rue Eugène Berthoud';
        $locationItem->geometry = $junctionGeometry;
        $locationItem->measures = [$measureCommand];

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

        $locationCommand = new SaveRegulationLocationCommand($regulationOrderRecord);
        $locationCommand->cityCode = '75056';
        $locationCommand->cityLabel = 'Paris';
        $locationCommand->roadName = 'Rue Eugène Berthoud';
        $locationCommand->fromHouseNumber = null;
        $locationCommand->toHouseNumber = null;
        $locationCommand->geometry = $junctionGeometry;
        $locationCommand->measures = [$measureCommand];

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
        $command = new ImportEudonetParisRegulationCommand($generalInfoCommand, [$locationItem]);

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
