<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Application\MattermostInterface;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\Litteralis\LitteralisExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:litteralis:import',
    description: 'Import Litteralis data of registered organizations',
    hidden: false,
)]
class LitteralisImportCommand extends Command
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        private array $litteralisEnabledOrgs,
        private LitteralisCredentials $litteralisCredentials,
        private Reporter $reporter,
        private LitteralisExecutor $executor,
        private DateUtilsInterface $dateUtils,
        private MattermostInterface $mattermost,
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

        foreach ($this->litteralisEnabledOrgs as $name) {
            $this->reporter->reset();

            try {
                $orgId = $this->litteralisCredentials->getOrgId($name);

                if (empty($orgId)) {
                    $output->writeln(\sprintf('Organization "%s": missing orgId (check APP_LITTERALIS_ORG_%s_ID)', $name, strtoupper($name)));
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
                $output->writeln(\sprintf('Organization "%s": import failed: %s', $name, $this->formatExceptionDetail($exc)));

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
        $lines = [];
        $lines[] = '#### Rapport d\'intégration Litteralis';
        $lines[] = 'Rapport généré le ' . $this->dateUtils->getNow()->format('d/m/Y H:i') . '.';
        $lines[] = '';

        foreach ($orgResults as $result) {
            $exception = $result['exception'] ?? null;

            if ($exception === null) {
                $lines[] = '- :white_check_mark: **' . $result['name'] . '** : Importé avec succès';
            } elseif ($this->isTimeout($exception)) {
                $lines[] = '- :warning: **' . $result['name'] . '** : Timeout / échec de connexion';
            } else {
                $lines[] = '- :x: **' . $result['name'] . '** : ' . $this->formatExceptionDetail($exception);
            }
        }

        $text = implode("\n", $lines);

        try {
            $this->mattermost->post($text);
            $this->logger->info('Rapport d\'intégration Litteralis envoyé sur Mattermost');
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l\'envoi du rapport Litteralis sur Mattermost', ['exception' => $e->getMessage()]);
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
