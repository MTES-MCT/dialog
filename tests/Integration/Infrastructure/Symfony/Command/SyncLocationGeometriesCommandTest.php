<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Symfony\Command\SyncLocationGeometriesCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncLocationGeometriesCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(SyncLocationGeometriesCommand::class);
        $commandTester = new CommandTester($command);

        $statusCode = $commandTester->execute([]);
        $this->assertSame(Command::FAILURE, $statusCode);

        $output = $commandTester->getDisplay();
        $logs = array_map(fn ($line) => json_decode($line, true), explode(PHP_EOL, trim($output)));
        $this->assertEquals([
            ['level' => 'ERROR', 'message' => 'some locations failed to be geocoded', 'num_locations' => 1, 'num_updated' => 0],
            ['level' => 'ERROR', 'message' => 'geocoding failed', 'location_uuid' => LocationFixture::UUID_FULL_CITY, 'exc' => 'not implemented: full city geocoding'],
        ], $logs);
    }
}
