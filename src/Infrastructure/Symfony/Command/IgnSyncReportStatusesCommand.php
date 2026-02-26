<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Infrastructure\Adapter\IgnReportClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:ign:sync-report-statuses',
    description: 'Synchronise les statuts des signalements IGN en interrogeant l\'API Espace collaboratif',
)]
class IgnSyncReportStatusesCommand extends Command
{
    public function __construct(
        private ReportAddressRepositoryInterface $reportAddressRepository,
        private IgnReportClient $ignReportClient,
        private EntityManagerInterface $entityManager,
        private DateUtilsInterface $dateUtils,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $emailSupport,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $reports = $this->reportAddressRepository->findAllPendingIgnReports();

        if (\count($reports) === 0) {
            $output->writeln('Aucun signalement IGN à synchroniser.');

            return Command::SUCCESS;
        }

        $updatedCount = 0;

        foreach ($reports as $report) {
            $ignReportId = $report->getIgnReportId();
            $newStatus = $this->ignReportClient->getReportStatus($ignReportId);

            if ($newStatus === null || $newStatus === $report->getIgnReportStatus()) {
                continue;
            }

            $report->setIgnReportStatus($newStatus);
            $report->setIgnStatusUpdatedAt($this->dateUtils->getNow());
            ++$updatedCount;

            $this->sendStatusUpdateNotification($report, $newStatus);
        }

        $this->entityManager->flush();

        $output->writeln(\sprintf('%d signalement(s) mis à jour sur %d interrogé(s).', $updatedCount, \count($reports)));

        return Command::SUCCESS;
    }

    private function sendStatusUpdateNotification(ReportAddress $report, string $status): void
    {
        try {
            $user = $report->getUser();
            $this->mailer->send(new Mail(
                address: $this->emailSupport,
                subject: 'contact.email.user_report_status_subject',
                template: 'email/user/user_report_status_updated.html.twig',
                payload: [
                    'content' => $report->getContent(),
                    'location' => $report->getLocation(),
                    'fullName' => $user->getFullName(),
                    'contactEmail' => $user->getEmail(),
                    'status' => $status,
                ],
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l\'envoi de la notification de changement de statut IGN', [
                'ignReportId' => $report->getIgnReportId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
