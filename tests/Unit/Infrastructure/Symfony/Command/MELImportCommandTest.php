<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\MEL\MELExecutor;
use App\Infrastructure\Symfony\Command\MELImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MELImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $executor = $this->createMock(MELExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute');

        $command = new MELImportCommand($executor);
        $this->assertSame('app:mel:import', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError()
    {
        $executor = $this->createMock(MELExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Failed'));

        $command = new MELImportCommand($executor);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
