<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommand;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommandResult;
use App\Infrastructure\Symfony\Command\OrientLocationGeometriesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class OrientLocationGeometriesCommandTest extends TestCase
{
    private const UUID1 = 'c4dcd553-1d59-4f14-9a5f-0ccddf3c24f7';
    private const UUID2 = '648cc626-a7c7-445c-9e52-9a8b3b13f40e';
    private const UUID3 = '0b5d0ddf-f7aa-4f0a-af12-1f654a505200';

    public function testExecute(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new RecomputeOneWayLocationGeometriesCommand(dryRun: false))
            ->willReturn(new RecomputeOneWayLocationGeometriesCommandResult(
                numLocations: 3,
                updatedLocationUuids: [self::UUID1, self::UUID2],
                unchangedLocationUuids: [self::UUID3],
                exceptions: [],
            ));

        $command = new OrientLocationGeometriesCommand($commandBus);
        $this->assertSame('app:location:geometry:orient', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $this->assertEquals([
            ['level' => 'INFO', 'message' => 'success', 'dry_run' => false, 'num_locations' => 3, 'num_updated' => 2, 'num_unchanged' => 1],
            ['level' => 'DEBUG', 'message' => 'updated', 'location_uuid' => self::UUID1],
            ['level' => 'DEBUG', 'message' => 'updated', 'location_uuid' => self::UUID2],
        ], $this->decodeLogs($commandTester));
    }

    public function testExecuteDryRun(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new RecomputeOneWayLocationGeometriesCommand(dryRun: true))
            ->willReturn(new RecomputeOneWayLocationGeometriesCommandResult(
                numLocations: 1,
                updatedLocationUuids: [self::UUID1],
                unchangedLocationUuids: [],
                exceptions: [],
            ));

        $commandTester = new CommandTester(new OrientLocationGeometriesCommand($commandBus));
        $commandTester->execute(['--dry-run' => true]);
        $commandTester->assertCommandIsSuccessful();

        $this->assertEquals([
            ['level' => 'INFO', 'message' => 'success', 'dry_run' => true, 'num_locations' => 1, 'num_updated' => 1, 'num_unchanged' => 0],
            ['level' => 'DEBUG', 'message' => 'would update', 'location_uuid' => self::UUID1],
        ], $this->decodeLogs($commandTester));
    }

    public function testExecuteWithExceptions(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->willReturn(new RecomputeOneWayLocationGeometriesCommandResult(
                numLocations: 2,
                updatedLocationUuids: [self::UUID1],
                unchangedLocationUuids: [],
                exceptions: [self::UUID2 => new GeocodingFailureException('oops')],
            ));

        $commandTester = new CommandTester(new OrientLocationGeometriesCommand($commandBus));
        $this->assertSame(Command::FAILURE, $commandTester->execute([]));

        $this->assertEquals([
            ['level' => 'ERROR', 'message' => 'some locations failed to be geocoded', 'dry_run' => false, 'num_locations' => 2, 'num_updated' => 1, 'num_unchanged' => 0],
            ['level' => 'DEBUG', 'message' => 'updated', 'location_uuid' => self::UUID1],
            ['level' => 'ERROR', 'message' => 'geocoding failed', 'location_uuid' => self::UUID2, 'exc' => 'oops'],
        ], $this->decodeLogs($commandTester));
    }

    private function decodeLogs(CommandTester $commandTester): array
    {
        return array_map(fn ($line) => json_decode($line, true), explode(PHP_EOL, trim($commandTester->getDisplay())));
    }
}
