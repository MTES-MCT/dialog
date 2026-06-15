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
    public function testRefusesOnProdEnv(): void
    {
        $connection = $this->createMock(Connection::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $connection->expects(self::never())->method('executeStatement');
        $passwordHasher->expects(self::never())->method('hash');

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, true);
        $tester = new CommandTester($command);

        $this->assertSame('app:db:anonymize', $command->getName());

        $tester->execute(['--force' => true]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('IS_PROD=true', $tester->getDisplay());
    }

    public function testRequiresForceOption(): void
    {
        $connection = $this->createMock(Connection::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $connection->expects(self::never())->method('executeStatement');
        $passwordHasher->expects(self::never())->method('hash');

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, false);
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
            ->with(self::matchesRegularExpression('/^[A-Za-z0-9]{32}$/'))
            ->willReturn('hashed-password');

        $executed = [];
        $connection
            ->expects(self::exactly(8))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$executed): int {
                $executed[] = ['sql' => $sql, 'params' => $params];

                return 1;
            });

        $command = new AnonymizeDatabaseCommand($connection, $passwordHasher, false);
        $tester = new CommandTester($command);

        $tester->execute(['--force' => true]);

        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        // SymfonyStyle retourne le message à la ligne : on normalise les espaces avant l'assertion.
        $normalizedDisplay = preg_replace('/\s+/', ' ', $display);
        $this->assertStringContainsString('Anonymisation terminée', $normalizedDisplay);
        $this->assertMatchesRegularExpression('/Mot de passe généré pour les autres utilisateurs : [A-Za-z0-9]{32}/', $normalizedDisplay);

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
}
