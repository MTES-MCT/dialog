<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\TchapInterface;
use App\Infrastructure\Symfony\Command\BdTopoNotifyCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BdTopoNotifyCommandTest extends TestCase
{
    private $logger;
    private $tchap;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tchap = $this->createMock(TchapInterface::class);
    }

    private function execute(array $input): CommandTester
    {
        $commandTester = new CommandTester(new BdTopoNotifyCommand($this->logger, $this->tchap));
        $commandTester->execute($input);

        return $commandTester;
    }

    private function expectPostedBody(callable $assertBody): void
    {
        $this->tchap
            ->expects(self::once())
            ->method('post')
            ->with(self::callback(function (string $body) use ($assertBody): bool {
                $assertBody($body);

                return true;
            }));
    }

    public function testNotifySuccess(): void
    {
        $this->tchap
            ->expects(self::once())
            ->method('post')
            ->with(
                self::callback(function (string $body): bool {
                    self::assertStringContainsString('Mise à jour BD TOPO 2025', $body);
                    self::assertStringContainsString('✅ Mise à jour réussie', $body);
                    self::assertStringContainsString('Millésime : 15/09/2025', $body);
                    self::assertStringContainsString('https://github.com/run/42', $body);
                    self::assertStringNotContainsString('Étape(s) en échec', $body);

                    return true;
                }),
                self::callback(function (string $formattedBody): bool {
                    self::assertStringContainsString('<h4>Mise à jour BD TOPO 2025</h4>', $formattedBody);
                    self::assertStringContainsString('<li>Millésime : 15/09/2025</li>', $formattedBody);
                    self::assertStringContainsString('<a href="https://github.com/run/42">', $formattedBody);

                    return true;
                }),
            );

        $commandTester = $this->execute([
            '--target' => '2025',
            '--result' => ['prepare=success', 'import=success', 'finalize=success'],
            '--millesime' => '2025-09-15',
            '--run-url' => 'https://github.com/run/42',
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testNotifySkipImport(): void
    {
        $this->expectPostedBody(function (string $body): void {
            self::assertStringContainsString('✅ Geopackages rafraîchis sur S3 (import ignoré)', $body);
        });

        $commandTester = $this->execute([
            '--target' => '2025',
            '--result' => ['prepare=success', 'import=skipped', 'finalize=skipped'],
            '--skip-import' => true,
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testNotifyFailureListsFailedSteps(): void
    {
        $this->expectPostedBody(function (string $body): void {
            self::assertStringContainsString('Mise à jour BD TOPO 2025_2', $body);
            self::assertStringContainsString('❌ Échec de la mise à jour', $body);
            self::assertStringContainsString('Étape(s) en échec : import des tables (failure)', $body);
            self::assertStringNotContainsString('finalisation', $body);
            self::assertStringNotContainsString('Millésime', $body);
        });

        $commandTester = $this->execute([
            '--target' => '2025_2',
            '--result' => ['finalize=skipped', 'import=failure', 'prepare=success'],
            '--millesime' => '',
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testNotifyCancelled(): void
    {
        $this->expectPostedBody(function (string $body): void {
            self::assertStringContainsString('⚠️ Mise à jour annulée', $body);
            self::assertStringContainsString('Étape(s) en échec : import des tables (cancelled)', $body);
        });

        $commandTester = $this->execute([
            '--target' => '2025',
            '--result' => ['prepare=success', 'import=cancelled', 'finalize=skipped'],
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public static function provideInvalidResults(): iterable
    {
        yield 'unknown result' => [['prepare=succes', 'import=success', 'finalize=success']];
        yield 'unknown step' => [['prepare=success', 'import=success', 'finalize=success', 'deploy=success']];
        yield 'duplicated step' => [['prepare=success', 'prepare=failure', 'import=success', 'finalize=success']];
        yield 'missing step' => [['prepare=success', 'import=success']];
        yield 'empty result' => [['prepare=', 'import=success', 'finalize=success']];
    }

    /**
     * @dataProvider provideInvalidResults
     */
    public function testInvalidResults(array $results): void
    {
        $this->tchap->expects(self::never())->method('post');

        $commandTester = $this->execute(['--target' => '2025', '--result' => $results]);

        self::assertSame(Command::INVALID, $commandTester->getStatusCode());
    }

    public function testInvalidMillesime(): void
    {
        $this->tchap->expects(self::never())->method('post');

        $commandTester = $this->execute([
            '--target' => '2025',
            '--result' => ['prepare=success', 'import=success', 'finalize=success'],
            '--millesime' => '2025-13-01',
        ]);

        self::assertSame(Command::INVALID, $commandTester->getStatusCode());
    }

    public function testReturnsFailureWhenTchapThrows(): void
    {
        $this->tchap
            ->method('post')
            ->willThrowException(new \RuntimeException('boom'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        $commandTester = $this->execute([
            '--target' => '2025',
            '--result' => ['prepare=success', 'import=success', 'finalize=success'],
        ]);

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
