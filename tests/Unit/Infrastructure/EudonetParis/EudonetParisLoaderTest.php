<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Infrastructure\EudonetParis\EudonetParisLoader;
use PHPUnit\Framework\TestCase;

final class EudonetParisLoaderTest extends TestCase
{
    private $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testLoad(): void
    {
        $command = $this->createMock(ImportEudonetParisRegulationCommand::class);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($command);

        $loader = new EudonetParisLoader($this->commandBus);

        $this->assertEmpty($loader->load($command));
    }
}
