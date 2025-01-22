<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\Integration\BacIdf\BacIdfExecutor;
use App\Infrastructure\Integration\BacIdf\Exception\BacIdfException;
use App\Infrastructure\Symfony\Command\BacIdfImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BacIdfImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $executor = $this->createMock(BacIdfExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute');

        $command = new BacIdfImportCommand($executor);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:bac_idf:import', $command->getName());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError()
    {
        $executor = $this->createMock(BacIdfExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute')
            ->with()
            ->willThrowException(new BacIdfException('Failed'));

        $command = new BacIdfImportCommand($executor);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:bac_idf:import', $command->getName());

        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
