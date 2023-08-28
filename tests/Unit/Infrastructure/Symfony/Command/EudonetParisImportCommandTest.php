<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\EudonetParis\EudonetParisExecutionReport;
use App\Infrastructure\EudonetParis\EudonetParisExecutor;
use App\Infrastructure\EudonetParis\EudonetParisLogger;
use App\Infrastructure\Symfony\Command\EudonetParisImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class EudonetParisImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $executor = $this->createMock(EudonetParisExecutor::class);
        $logger = $this->createMock(EudonetParisLogger::class);
        $report = $this->createMock(EudonetParisExecutionReport::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);

        $now = new \DateTimeImmutable('now');

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $executor
            ->expects(self::once())
            ->method('execute')
            ->with()
            ->willReturn($report);

        $report
            ->expects(self::once())
            ->method('getContent')
            ->willReturn('Report content');

        $report
            ->expects(self::once())
            ->method('hasError')
            ->willReturn(false);

        $logger
            ->expects(self::once())
            ->method('log')
            ->with('Report content', $now);

        $command = new EudonetParisImportCommand($executor, $logger, $dateUtils);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:eudonet_paris:import', $command->getName());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertEquals('Report content', $output);
    }

    public function testExecuteError()
    {
        $executor = $this->createMock(EudonetParisExecutor::class);
        $logger = $this->createMock(EudonetParisLogger::class);
        $report = $this->createMock(EudonetParisExecutionReport::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);

        $executor
            ->expects(self::once())
            ->method('execute')
            ->with()
            ->willReturn($report);

        $report
            ->expects(self::once())
            ->method('hasError')
            ->willReturn(true);

        $report
            ->expects(self::once())
            ->method('getContent')
            ->willReturn('ERROR: ...');

        $command = new EudonetParisImportCommand($executor, $logger, $dateUtils);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:eudonet_paris:import', $command->getName());

        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertEquals('ERROR: ...', $output);
    }
}
