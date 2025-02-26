<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommand;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommandResult;
use App\Infrastructure\Symfony\Command\SyncLocationGeometriesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SyncLocationGeometriesCommandTest extends TestCase
{
    public function testExecute()
    {
        $commandBus = $this->createMock(CommandBusInterface::class);

        $uuid1 = 'c4dcd553-1d59-4f14-9a5f-0ccddf3c24f7';
        $uuid2 = '648cc626-a7c7-445c-9e52-9a8b3b13f40e';

        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GeocodeLocationsWithoutGeometryCommand())
            ->willReturn(new GeocodeLocationsWithoutGeometryCommandResult(
                numLocations: 2,
                updatedLocationUuids: [$uuid1, $uuid2],
                exceptions: [],
            ));

        $command = new SyncLocationGeometriesCommand($commandBus);
        $this->assertSame('app:location:geometry:sync', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $logLines = explode(PHP_EOL, trim($output));

        $this->assertEquals([
            ['level' => 'INFO', 'message' => 'success', 'num_locations' => 2, 'num_updated' => 2],
            ['level' => 'DEBUG', 'message' => 'updated', 'location_uuid' => $uuid1],
            ['level' => 'DEBUG', 'message' => 'updated', 'location_uuid' => $uuid2],
        ], array_map(fn ($line) => json_decode($line, true), $logLines));
    }

    public function testExecuteWithExceptions()
    {
        $commandBus = $this->createMock(CommandBusInterface::class);

        $uuid1 = 'c4dcd553-1d59-4f14-9a5f-0ccddf3c24f7';
        $uuid2 = '648cc626-a7c7-445c-9e52-9a8b3b13f40e';

        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GeocodeLocationsWithoutGeometryCommand())
            ->willReturn(new GeocodeLocationsWithoutGeometryCommandResult(
                numLocations: 2,
                updatedLocationUuids: [$uuid1],
                exceptions: [
                    $uuid2 => new GeocodingFailureException('oops'),
                ],
            ));

        $command = new SyncLocationGeometriesCommand($commandBus);
        $this->assertSame('app:location:geometry:sync', $command->getName());

        $commandTester = new CommandTester($command);
        $statusCode = $commandTester->execute([]);
        $this->assertSame(Command::FAILURE, $statusCode);
        $output = $commandTester->getDisplay();
        $logLines = explode(PHP_EOL, trim($output));

        $this->assertEquals([
            ['level' => 'ERROR', 'message' => 'some locations failed to be geocoded', 'num_locations' => 2, 'num_updated' => 1],
            ['level' => 'DEBUG', 'message' => 'updated', 'location_uuid' => $uuid1],
            ['level' => 'ERROR', 'message' => 'geocoding failed', 'location_uuid' => $uuid2, 'exc' => 'oops'],
        ], array_map(fn ($line) => json_decode($line, true), $logLines));
    }
}
