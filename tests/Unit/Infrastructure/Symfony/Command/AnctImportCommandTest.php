<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\StringUtilsInterface;
use App\Infrastructure\Symfony\Command\AnctImportCommand;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AnctImportCommandTest extends TestCase
{
    private $em;
    private $connection;
    private $stringUtils;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->stringUtils = $this->createMock(StringUtilsInterface::class);
        $this->stringUtils->method('normalizeEmail')->willReturnCallback(fn (string $email) => strtolower($email));
    }

    private function createCommandTester(MockHttpClient $httpClient): CommandTester
    {
        return new CommandTester(new AnctImportCommand($this->em, $httpClient, $this->stringUtils));
    }

    public function testDownloadGzippedFileFromUrlAndInsertInBatches(): void
    {
        $territories = [];
        for ($i = 0; $i < 1001; ++$i) {
            $territories[] = ['siret' => \sprintf('%014d', $i), 'adresse_messagerie' => \sprintf('Contact%d@Example.fr', $i)];
        }
        $territories[] = ['siret' => '', 'adresse_messagerie' => 'empty-siret@example.fr'];
        $territories[] = ['siret' => '99999999999999'];

        $httpClient = new MockHttpClient(function (string $method, string $url) use ($territories): MockResponse {
            $this->assertSame('GET', $method);
            $this->assertSame('https://example.org/dpnt.json.gz', $url);

            return new MockResponse(gzencode(json_encode($territories)));
        });

        $this->em->expects(self::once())->method('getConnection')->willReturn($this->connection);
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('commit');
        $this->connection->expects(self::never())->method('rollBack');

        $statements = [];
        $this->connection
            ->expects(self::exactly(3))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$statements): int {
                $statements[] = [$sql, $params];

                return 0;
            });

        $commandTester = $this->createCommandTester($httpClient);
        $commandTester->execute(['--url' => 'https://example.org/dpnt.json.gz']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('1001 collectivités', $commandTester->getDisplay());
        $this->assertSame('TRUNCATE TABLE anct_territory_contact', $statements[0][0]);
        $this->assertCount(2000, $statements[1][1]);
        $this->assertSame(['00000000000000', 'contact0@example.fr'], \array_slice($statements[1][1], 0, 2));
        $this->assertSame(['00000000001000', 'contact1000@example.fr'], $statements[2][1]);
    }

    public function testUnreadableFile(): void
    {
        $this->em->expects(self::never())->method('getConnection');

        $commandTester = $this->createCommandTester(new MockHttpClient());
        $commandTester->execute(['--file' => '/path/to/unknown/file.json']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Impossible de lire le fichier', $commandTester->getDisplay());
    }

    public function testInvalidGzipFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible de décompresser le fichier gzip.');

        $httpClient = new MockHttpClient(new MockResponse("\x1f\x8binvalid"));

        $commandTester = $this->createCommandTester($httpClient);
        $commandTester->execute([]);
    }

    public function testRollbackOnDatabaseError(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            ['siret' => '21930001000019', 'adresse_messagerie' => 'contact@ville-test.fr'],
        ])));

        $this->em->expects(self::once())->method('getConnection')->willReturn($this->connection);
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection
            ->expects(self::once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('Database error'));
        $this->connection->expects(self::never())->method('commit');
        $this->connection->expects(self::once())->method('rollBack');

        $commandTester = $this->createCommandTester($httpClient);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Import interrompu : Database error', $commandTester->getDisplay());
    }
}
