<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\BdTopoSetupIndexesCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BdTopoSetupIndexesCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects(self::exactly(11))
            ->method('executeStatement');

        $command = new BdTopoSetupIndexesCommand($connection);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:bdtopo:setup_indexes', $command->getName());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('created successfully', $commandTester->getDisplay());
    }

    public function testExecuteWithError(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql): int {
                if (str_contains($sql, 'voie_nommee_identifiant_voie_ban_idx')) {
                    throw new DBALException('relation "voie_nommee" does not exist');
                }

                return 0;
            });

        $command = new BdTopoSetupIndexesCommand($connection);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('1 statement(s) failed', $display);
        $this->assertStringContainsString('voie_nommee', $display);
    }

    public function testExecuteWithMultipleErrors(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('executeStatement')
            ->willThrowException(new DBALException('connection refused'));

        $command = new BdTopoSetupIndexesCommand($connection);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('11 statement(s) failed', $commandTester->getDisplay());
    }

    public function testExecuteRecordsMillesime(): void
    {
        $connection = $this->createMock(Connection::class);

        // 11 statements de setup + TRUNCATE + INSERT du millésime.
        $connection
            ->expects(self::exactly(13))
            ->method('executeStatement');

        $command = new BdTopoSetupIndexesCommand($connection);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--millesime' => '2026-08-09']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('2026-08-09', $commandTester->getDisplay());
    }

    public function testExecuteRejectsInvalidMillesime(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects(self::never())
            ->method('executeStatement');

        $command = new BdTopoSetupIndexesCommand($connection);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--millesime' => '09/08/2026']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('invalide', $commandTester->getDisplay());
    }
}
