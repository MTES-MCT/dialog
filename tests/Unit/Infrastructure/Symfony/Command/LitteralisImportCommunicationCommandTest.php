<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Application\TchapInterface;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationExecutor;
use App\Infrastructure\Symfony\Command\LitteralisImportCommunicationCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class LitteralisImportCommunicationCommandTest extends TestCase
{
    private array $enabledOrgs;
    private LitteralisCredentials $credentials;
    private $logger;
    private $reporter;
    private $executor;
    private $dateUtils;
    private $tchap;

    protected function setUp(): void
    {
        $this->enabledOrgs = ['mel', 'fougeres'];

        $this->credentials = (new LitteralisCredentials())
            ->add('mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'melpassword')
            ->add('fougeres', 'fe8ef74d-c948-4782-b6dd-979ca9b8ca67', 'fougerespassword');

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = $this->createMock(Reporter::class);
        $this->executor = $this->createMock(LitteralisCommunicationExecutor::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->tchap = $this->createMock(TchapInterface::class);
    }

    private function createCommand(): LitteralisImportCommunicationCommand
    {
        return new LitteralisImportCommunicationCommand(
            $this->logger,
            $this->enabledOrgs,
            $this->credentials,
            $this->reporter,
            $this->executor,
            $this->dateUtils,
            $this->tchap,
        );
    }

    public function testExecuteCallsExecutorForEachEnabledOrgAndSendsReport(): void
    {
        $now = new \DateTimeImmutable();

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('getNow')
            ->willReturn($now);

        $this->reporter
            ->expects(self::once())
            ->method('setLogger')
            ->with($this->logger);

        $this->executor
            ->expects(self::exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', $now, $this->reporter],
                ['fougeres', 'fe8ef74d-c948-4782-b6dd-979ca9b8ca67', $now, $this->reporter],
            )
            ->willReturn('Rapport Communication');

        $this->tchap
            ->expects(self::once())
            ->method('post')
            ->with(self::callback(function (string $body): bool {
                return str_contains($body, 'Rapport d\'intégration Litteralis (Communication)')
                    && str_contains($body, 'mel')
                    && str_contains($body, 'fougeres');
            }));

        $command = $this->createCommand();
        $this->assertSame('app:litteralis:import-communication', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteReportsMissingOrgIdAndContinues(): void
    {
        $this->enabledOrgs = ['mel', 'unknown_org'];
        $this->credentials = (new LitteralisCredentials())
            ->add('mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'melpassword');

        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);

        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->with('mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', $now, $this->reporter)
            ->willReturn('Rapport');

        $commandTester = new CommandTester($this->createCommand());
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('missing orgId', $commandTester->getDisplay());
        $this->assertStringContainsString('unknown_org', $commandTester->getDisplay());
    }

    public function testExecuteWhenExecutorThrowsReportsFailureAndSendsReport(): void
    {
        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);
        $this->enabledOrgs = ['mel'];

        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->with('mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', $now, $this->reporter)
            ->willThrowException(new \RuntimeException('Something went wrong'));

        $sentBody = null;
        $this->tchap
            ->expects(self::once())
            ->method('post')
            ->willReturnCallback(function (string $body) use (&$sentBody): void {
                $sentBody = $body;
            });

        $commandTester = new CommandTester($this->createCommand());
        $commandTester->execute([]);

        $this->assertStringContainsString('import failed', $commandTester->getDisplay());
        $this->assertStringContainsString('Something went wrong', $commandTester->getDisplay());
        $this->assertNotNull($sentBody);
        $this->assertStringContainsString('❌ mel', $sentBody);
        $this->assertStringContainsString('Something went wrong', $sentBody);
    }

    public function testExecuteWhenExecutorThrowsTimeoutSendsReportWithWarning(): void
    {
        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);
        $this->enabledOrgs = ['mel'];

        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new class extends \Exception implements TransportExceptionInterface {
            });

        $this->tchap
            ->expects(self::once())
            ->method('post')
            ->with(self::callback(function (string $body): bool {
                return str_contains($body, '⚠️ mel');
            }));

        $commandTester = new CommandTester($this->createCommand());
        $commandTester->execute([]);
    }

    public function testExecuteWhenTchapPostThrowsLogsError(): void
    {
        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);
        $this->executor->method('execute')->willReturn('Rapport');

        $this->tchap
            ->expects(self::once())
            ->method('post')
            ->willThrowException(new \RuntimeException('Tchap unreachable'));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Échec de l\'envoi du rapport Litteralis sur Tchap', self::callback(function (array $context): bool {
                return isset($context['exception'])
                    && $context['exception'] === 'Tchap unreachable';
            }));

        $commandTester = new CommandTester($this->createCommand());
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }
}
