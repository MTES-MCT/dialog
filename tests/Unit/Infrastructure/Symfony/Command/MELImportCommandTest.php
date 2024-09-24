<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\MEL\MELExecutor;
use App\Infrastructure\Symfony\Command\MELImportCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MELImportCommandTest extends TestCase
{
    private $logger;
    private $executor;
    private $dateUtils;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->executor = $this->createMock(MELExecutor::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecute(): void
    {
        $now = new \DateTimeImmutable('now');
        $reporter = new Reporter($this->logger);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->with($now, self::equalTo($reporter));

        $command = new MELImportCommand($this->logger, $this->executor, $this->dateUtils);
        $this->assertSame('app:mel:import', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError(): void
    {
        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Failed'));

        $command = new MELImportCommand($this->logger, $this->executor, $this->dateUtils);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
