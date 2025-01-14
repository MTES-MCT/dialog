<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\Litteralis\LonsLeSaunier\LonsLeSaunierExecutor;
use App\Infrastructure\Symfony\Command\LonsLeSaunierImportCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LonsLeSaunierImportCommandTest extends TestCase
{
    private $logger;
    private $executor;
    private $dateUtils;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->executor = $this->createMock(LonsLeSaunierExecutor::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecute()
    {
        $this->executor
            ->expects(self::once())
            ->method('execute');

        $command = new LonsLeSaunierImportCommand($this->logger, $this->executor, $this->dateUtils);
        $this->assertSame('app:lons_le_saunier:import', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError()
    {
        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Failed'));

        $command = new LonsLeSaunierImportCommand($this->logger, $this->executor, $this->dateUtils);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
