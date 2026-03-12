<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationExecutor;
use App\Infrastructure\Symfony\Command\LitteralisImportCommunicationCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class LitteralisImportCommunicationCommandTest extends TestCase
{
    private const EMAIL_SUPPORT = 'support@dialog.beta.gouv.fr';

    private array $enabledOrgs;
    private LitteralisCredentials $credentials;
    private $logger;
    private $reporter;
    private $executor;
    private $dateUtils;
    private $mailer;

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
        $this->mailer = $this->createMock(MailerInterface::class);
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

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Mail $mail): bool {
                return $mail->address === self::EMAIL_SUPPORT
                    && $mail->template === 'email/litteralis/support_report.html.twig';
            }));

        $command = new LitteralisImportCommunicationCommand(
            $this->logger,
            $this->enabledOrgs,
            $this->credentials,
            $this->reporter,
            $this->executor,
            $this->dateUtils,
            $this->mailer,
            self::EMAIL_SUPPORT,
        );

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
        // unknown_org has no ID => getOrgId returns null/empty

        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);

        $this->executor
            ->expects(self::once())
            ->method('execute')
            ->with('mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', $now, $this->reporter)
            ->willReturn('Rapport');

        $command = new LitteralisImportCommunicationCommand(
            $this->logger,
            $this->enabledOrgs,
            $this->credentials,
            $this->reporter,
            $this->executor,
            $this->dateUtils,
            $this->mailer,
            self::EMAIL_SUPPORT,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('missing orgId', $commandTester->getDisplay());
        $this->assertStringContainsString('unknown_org', $commandTester->getDisplay());
    }
}
