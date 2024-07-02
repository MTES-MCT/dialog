<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\JOP\JOPExecutor;
use App\Infrastructure\Symfony\Command\JOPImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class JOPImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $executor = $this->createMock(JOPExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute');

        $command = new JOPImportCommand($executor);
        $this->assertSame('app:jop:import', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError()
    {
        $executor = $this->createMock(JOPExecutor::class);

        $executor
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Failed'));

        $command = new JOPImportCommand($executor);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
