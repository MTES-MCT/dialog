<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\Litteralis\Fougeres\FougeresExecutor;
use App\Infrastructure\Symfony\Command\FougeresImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class FougeresImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $executor = $this->createMock(FougeresExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute');

        $command = new FougeresImportCommand($executor);
        $this->assertSame('app:fougeres:import', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError()
    {
        $executor = $this->createMock(FougeresExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Failed'));

        $command = new FougeresImportCommand($executor);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
