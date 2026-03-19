<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Import Litteralis via le flux WFS "Communication" (couche LIcommunication).
 * Coexiste avec app:litteralis:import (flux standard) le temps de la migration.
 *
 * Organisations à traiter : APP_LITTERALIS_COMMUNICATION_ENABLED_ORGS (JSON).
 * Identifiants : mêmes que le flux standard (APP_LITTERALIS_ORG_<NAME>_ID, _CREDENTIALS).
 */
#[AsCommand(
    name: 'app:litteralis:import-communication',
    description: 'Import Litteralis data via WFS Communication flux (LIcommunication) for configured organizations',
    hidden: false,
)]
class LitteralisImportCommunicationCommand extends Command
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        private array $litteralisCommunicationEnabledOrgs,
        private LitteralisCredentials $litteralisCredentials,
        private Reporter $reporter,
        private LitteralisCommunicationExecutor $executor,
        private DateUtilsInterface $dateUtils,
        private MailerInterface $mailer,
        private string $emailSupport,
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->reporter->setLogger($logger);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->dateUtils->getNow();

        $returnCode = Command::SUCCESS;
        $orgResults = [];

        foreach ($this->litteralisCommunicationEnabledOrgs as $name) {
            $this->reporter->reset();

            try {
                $orgId = $this->litteralisCredentials->getOrgId($name);

                if (empty($orgId)) {
                    $output->writeln(\sprintf(
                        'Organization "%s": missing orgId (check APP_LITTERALIS_ORG_%s_ID)',
                        $name,
                        strtoupper($name),
                    ));
                    $returnCode = Command::FAILURE;
                    continue;
                }

                $report = $this->executor->execute($name, $orgId, $now, $this->reporter);

                $orgResults[] = [
                    'name' => $name,
                    'report' => $report,
                    'exception' => null,
                ];

                $output->write($report);
            } catch (\Throwable $exc) {
                $output->writeln(\sprintf(
                    'Organization "%s": import failed: %s',
                    $name,
                    $this->formatExceptionDetail($exc),
                ));

                $orgResults[] = [
                    'name' => $name,
                    'report' => null,
                    'exception' => $exc,
                ];
            }
        }

        if ($orgResults !== []) {
            $output->writeln('Sending support report...');

            $this->sendSupportReport($orgResults);
        }

        return $returnCode;
    }

    private function sendSupportReport(array $orgResults): void
    {
        $orgSummaries = [];

        foreach ($orgResults as $result) {
            $exception = $result['exception'] ?? null;
            $orgSummaries[] = [
                'name' => $result['name'],
                'success' => $exception === null,
                'isTimeout' => $exception !== null && $this->isTimeout($exception),
                'failureMessage' => $exception !== null ? $this->formatExceptionDetail($exception) : null,
            ];
        }

        try {
            $this->mailer->send(new Mail(
                address: $this->emailSupport,
                subject: 'litteralis.support_report.subject',
                template: 'email/litteralis/support_report.html.twig',
                payload: [
                    'orgSummaries' => $orgSummaries,
                    'reportDate' => $this->dateUtils->getNow()->format('d/m/Y H:i'),
                ],
            ));
            $this->logger->info('Rapport d\'intégration Litteralis (Communication) envoyé par mail', [
                'address' => $this->emailSupport,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l\'envoi du rapport Litteralis par mail', [
                'address' => $this->emailSupport,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function formatExceptionDetail(\Throwable $e): string
    {
        $msg = $e->getMessage();

        return $msg !== '' ? $msg . ' (' . $e::class . ')' : $e::class;
    }

    private function isTimeout(\Throwable $e): bool
    {
        if ($e instanceof TransportExceptionInterface) {
            return true;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'timeout') || str_contains($message, 'timed out');
    }
}
