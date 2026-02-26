<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\MailerInterface;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Adapter\IgnReportClient;
use App\Infrastructure\Symfony\Command\IgnSyncReportStatusesCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class IgnSyncReportStatusesCommandTest extends TestCase
{
    private MockObject $reportAddressRepository;
    private MockObject $ignReportClient;
    private MockObject $entityManager;
    private MockObject $dateUtils;
    private MockObject $mailer;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->reportAddressRepository = $this->createMock(ReportAddressRepositoryInterface::class);
        $this->ignReportClient = $this->createMock(IgnReportClient::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function createCommandTester(): CommandTester
    {
        $command = new IgnSyncReportStatusesCommand(
            $this->reportAddressRepository,
            $this->ignReportClient,
            $this->entityManager,
            $this->dateUtils,
            $this->mailer,
            $this->logger,
            'support@example.com',
        );

        return new CommandTester($command);
    }

    public function testNoReportsToSync(): void
    {
        $this->reportAddressRepository
            ->expects(self::once())
            ->method('findAllPendingIgnReports')
            ->willReturn([]);

        $this->ignReportClient->expects(self::never())->method('getReportStatus');
        $this->entityManager->expects(self::never())->method('flush');

        $tester = $this->createCommandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Aucun signalement IGN', $tester->getDisplay());
    }

    public function testStatusUnchangedSkipsUpdate(): void
    {
        $user = $this->createMock(User::class);
        $report = new ReportAddress('uuid-1', 'contenu', 'lieu', $user);
        $report->setIgnReportId('ign-123');
        $report->setIgnReportStatus('submit');

        $this->reportAddressRepository
            ->method('findAllPendingIgnReports')
            ->willReturn([$report]);

        $this->ignReportClient
            ->expects(self::once())
            ->method('getReportStatus')
            ->with('ign-123')
            ->willReturn('submit');

        $this->entityManager->expects(self::once())->method('flush');
        $this->mailer->expects(self::never())->method('send');

        $tester = $this->createCommandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('0 signalement(s) mis à jour sur 1', $tester->getDisplay());
    }

    public function testStatusChangedUpdatesAndSendsEmail(): void
    {
        $now = new \DateTimeImmutable('2025-03-01 10:00:00');
        $user = $this->createMock(User::class);
        $user->method('getFullName')->willReturn('Jean Dupont');
        $user->method('getEmail')->willReturn('jean@example.com');

        $report = new ReportAddress('uuid-1', 'contenu', 'lieu', $user);
        $report->setIgnReportId('ign-123');
        $report->setIgnReportStatus('submit');

        $this->reportAddressRepository
            ->method('findAllPendingIgnReports')
            ->willReturn([$report]);

        $this->ignReportClient
            ->expects(self::once())
            ->method('getReportStatus')
            ->with('ign-123')
            ->willReturn('treated');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->entityManager->expects(self::once())->method('flush');
        $this->mailer->expects(self::once())->method('send');

        $tester = $this->createCommandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('1 signalement(s) mis à jour sur 1', $tester->getDisplay());
        $this->assertSame('treated', $report->getIgnReportStatus());
        $this->assertSame($now, $report->getIgnStatusUpdatedAt());
    }

    public function testApiReturnsNullSkipsUpdate(): void
    {
        $user = $this->createMock(User::class);
        $report = new ReportAddress('uuid-1', 'contenu', 'lieu', $user);
        $report->setIgnReportId('ign-123');
        $report->setIgnReportStatus('submit');

        $this->reportAddressRepository
            ->method('findAllPendingIgnReports')
            ->willReturn([$report]);

        $this->ignReportClient
            ->expects(self::once())
            ->method('getReportStatus')
            ->with('ign-123')
            ->willReturn(null);

        $this->entityManager->expects(self::once())->method('flush');
        $this->mailer->expects(self::never())->method('send');

        $tester = $this->createCommandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('0 signalement(s) mis à jour sur 1', $tester->getDisplay());
        $this->assertSame('submit', $report->getIgnReportStatus());
    }

    public function testEmailFailureDoesNotStopSync(): void
    {
        $now = new \DateTimeImmutable('2025-03-01 10:00:00');
        $user = $this->createMock(User::class);
        $user->method('getFullName')->willReturn('Jean Dupont');
        $user->method('getEmail')->willReturn('jean@example.com');

        $report1 = new ReportAddress('uuid-1', 'contenu1', 'lieu1', $user);
        $report1->setIgnReportId('ign-1');
        $report1->setIgnReportStatus('submit');

        $report2 = new ReportAddress('uuid-2', 'contenu2', 'lieu2', $user);
        $report2->setIgnReportId('ign-2');
        $report2->setIgnReportStatus('submit');

        $this->reportAddressRepository
            ->method('findAllPendingIgnReports')
            ->willReturn([$report1, $report2]);

        $this->ignReportClient
            ->expects(self::exactly(2))
            ->method('getReportStatus')
            ->willReturn('treated');

        $this->dateUtils->method('getNow')->willReturn($now);
        $this->entityManager->expects(self::once())->method('flush');

        $this->mailer
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                self::throwException(new \Exception('SMTP error')),
                null,
            );

        $this->logger->expects(self::once())->method('error');

        $tester = $this->createCommandTester();
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('2 signalement(s) mis à jour sur 2', $tester->getDisplay());
    }
}
