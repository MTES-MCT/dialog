<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\PasswordHasherInterface;
use App\Infrastructure\Symfony\Command\AnonymizeDatabaseCommand;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AnonymizeDatabaseCommandTest extends TestCase
{
    public function testRefusesOnProdEnvWithoutAllowFlag(): void
    {
        $connection = $this->createMock(Connection::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $connection->expects(self::never())->method('executeStatement');
        $passwordHasher->expects(self::never())->method('hash');

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, 'prod');
        $tester = new CommandTester($command);

        $this->assertSame('app:db:anonymize', $command->getName());

        $tester->execute(['--force' => true]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('APP_ENV=prod', $tester->getDisplay());
    }

    public function testRequiresForceOption(): void
    {
        $connection = $this->createMock(Connection::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $connection->expects(self::never())->method('executeStatement');
        $passwordHasher->expects(self::never())->method('hash');

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, 'staging');
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('--force', $tester->getDisplay());
    }

    public function testExecutesAnonymizationWithForce(): void
    {
        $connection = $this->createMock(Connection::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('staging-password-reset-me')
            ->willReturn('hashed-password');

        $executed = [];
        $connection
            ->expects(self::exactly(8))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$executed): int {
                $executed[] = ['sql' => $sql, 'params' => $params];

                return 1;
            });

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, 'staging');
        $tester = new CommandTester($command);

        $tester->execute(['--force' => true]);

        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Anonymisation terminée', $display);
        $this->assertStringContainsString('staging-password-reset-me', $display);

        $sqls = array_column($executed, 'sql');
        $this->assertStringContainsString('UPDATE "user"', $sqls[0]);
        $this->assertStringContainsString("roles NOT LIKE '%ROLE_SUPER_ADMIN%'", $sqls[0]);
        $this->assertStringContainsString('UPDATE password_user', $sqls[1]);
        $this->assertSame(['hash' => 'hashed-password'], $executed[1]['params']);
        $this->assertStringContainsString('DELETE FROM token', $sqls[2]);
        $this->assertStringContainsString('DELETE FROM invitation', $sqls[3]);
        $this->assertStringContainsString('UPDATE feedback', $sqls[4]);
        $this->assertStringContainsString('UPDATE report_address', $sqls[5]);
        $this->assertStringContainsString('UPDATE mailing_list', $sqls[6]);
        $this->assertStringContainsString('UPDATE api_client', $sqls[7]);
    }

    public function testAllowsProdEnvWithAllowProdEnvFlag(): void
    {
        $connection = $this->createMock(Connection::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->willReturn('hashed-password');

        $connection
            ->expects(self::exactly(8))
            ->method('executeStatement');

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, 'prod');
        $tester = new CommandTester($command);

        $tester->execute(['--force' => true, '--allow-prod-env' => true]);

        $tester->assertCommandIsSuccessful();
    }
}
