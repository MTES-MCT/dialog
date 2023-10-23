<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\EudonetParis\EudonetParisExecutor;
use App\Infrastructure\EudonetParis\Exception\EudonetParisException;
use App\Infrastructure\Symfony\Command\EudonetParisImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class EudonetParisImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $executor = $this->createMock(EudonetParisExecutor::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);

        $now = new \DateTimeImmutable('now');

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $dateUtils
            ->expects(self::never())
            ->method('getMicroTime');

        $executor
            ->expects(self::once())
            ->method('execute')
            ->with($now);

        $command = new EudonetParisImportCommand($executor, $dateUtils);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:eudonet_paris:import', $command->getName());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError()
    {
        $executor = $this->createMock(EudonetParisExecutor::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);

        $now = new \DateTimeImmutable('now');

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $dateUtils
            ->expects(self::never())
            ->method('getMicroTime');

        $executor
            ->expects(self::once())
            ->method('execute')
            ->with($now)
            ->willThrowException(new EudonetParisException('Failed'));

        $command = new EudonetParisImportCommand($executor, $dateUtils);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:eudonet_paris:import', $command->getName());

        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
