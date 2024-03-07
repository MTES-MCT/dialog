<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Symfony\Command\SyncLocationsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncLocationsCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $location1 = $this->createMock(Location::class);
        $location2 = $this->createMock(Location::class);

        $locationRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$location1, $location2]);

        $commandBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([new SaveLocationCommand($location1)], [new SaveLocationCommand($location2)]);

        $command = new SyncLocationsCommand($locationRepository, $commandBus);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }
}
