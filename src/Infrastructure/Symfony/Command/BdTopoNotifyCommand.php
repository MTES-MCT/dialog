<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\TchapInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:bdtopo:notify',
    description: 'Envoie une notification Tchap sur le résultat de la mise à jour de la BD TOPO',
)]
class BdTopoNotifyCommand extends Command
{
    // Jobs du workflow bdtopo_update, dans l'ordre d'exécution.
    private const STEPS = [
        'prepare' => 'préparation (téléchargement IGN + upload S3)',
        'import' => 'import des tables',
        'finalize' => 'finalisation (index + millésime)',
    ];

    // Valeurs possibles de needs.<job>.result dans GitHub Actions.
    private const RESULTS = ['success', 'failure', 'cancelled', 'skipped'];

    public function __construct(
        private LoggerInterface $logger,
        private TchapInterface $tchap,
        private DateUtilsInterface $dateUtils,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Base BD TOPO cible (ex : 2025, 2025_2)')
            ->addOption('result', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, \sprintf('Résultat d\'un job, au format étape=résultat (étapes : %s ; résultats : %s)', implode(', ', array_keys(self::STEPS)), implode(', ', self::RESULTS)))
            ->addOption('skip-import', null, InputOption::VALUE_NONE, 'Le pipeline a été lancé sans import (seulement rafraîchissement des geopackages sur S3)')
            ->addOption('millesime', null, InputOption::VALUE_REQUIRED, 'Millésime BD TOPO lu dans le manifest S3 (format YYYY-MM-DD)')
            ->addOption('run-url', null, InputOption::VALUE_REQUIRED, "Lien vers l'exécution GitHub Actions");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $results = $this->parseResults($input->getOption('result'), $output);

        if (null === $results) {
            return Command::INVALID;
        }

        $target = (string) $input->getOption('target');
        $runUrl = (string) $input->getOption('run-url');
        $skipImport = (bool) $input->getOption('skip-import');
        $millesime = (string) $input->getOption('millesime');

        // Vide si le job prepare a échoué avant d'avoir lu le manifest.
        $millesimeDate = null;
        if ('' !== $millesime) {
            $millesimeDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $millesime);

            if (false === $millesimeDate || $millesimeDate->format('Y-m-d') !== $millesime) {
                $output->writeln(\sprintf('<error>Millésime invalide : "%s" (format attendu : YYYY-MM-DD)</error>', $millesime));

                return Command::INVALID;
            }
        }

        if (\in_array('cancelled', $results, true)) {
            $statusLine = '⚠️ Mise à jour annulée';
        } elseif ('success' === $results['prepare']
            && \in_array($results['import'], ['success', 'skipped'], true)
            && \in_array($results['finalize'], ['success', 'skipped'], true)) {
            $statusLine = $skipImport
                ? '✅ Geopackages rafraîchis sur S3 (import ignoré)'
                : '✅ Mise à jour réussie';
        } else {
            $statusLine = '❌ Échec de la mise à jour';
        }

        $failedSteps = [];
        foreach ($results as $step => $result) {
            if (\in_array($result, ['failure', 'cancelled'], true)) {
                $failedSteps[] = \sprintf('%s (%s)', self::STEPS[$step], $result);
            }
        }

        $title = '' !== $target
            ? \sprintf('Mise à jour BD TOPO %s', $target)
            : 'Mise à jour BD TOPO';
        $generatedAt = 'Notification générée le ' . $this->dateUtils->getNow()->format('d/m/Y H:i') . '.';

        $textLines = [$title, $generatedAt, '', $statusLine];
        $htmlItems = [\sprintf('<li>%s</li>', htmlspecialchars($statusLine, ENT_QUOTES))];

        if (null !== $millesimeDate) {
            $millesimeLine = \sprintf('Millésime : %s', $millesimeDate->format('d/m/Y'));
            $textLines[] = $millesimeLine;
            $htmlItems[] = \sprintf('<li>%s</li>', htmlspecialchars($millesimeLine, ENT_QUOTES));
        }

        if ($failedSteps) {
            $failedStepsLine = \sprintf('Étape(s) en échec : %s', implode(', ', $failedSteps));
            $textLines[] = $failedStepsLine;
            $htmlItems[] = \sprintf('<li>%s</li>', htmlspecialchars($failedStepsLine, ENT_QUOTES));
        }

        if ('' !== $runUrl) {
            $textLines[] = \sprintf('Détails : %s', $runUrl);
            $htmlItems[] = \sprintf('<li>Détails : <a href="%1$s">%1$s</a></li>', htmlspecialchars($runUrl, ENT_QUOTES));
        }

        $body = implode("\n", $textLines);
        $formattedBody = \sprintf(
            '<h4>%s</h4><p>%s</p><ul>%s</ul>',
            htmlspecialchars($title, ENT_QUOTES),
            htmlspecialchars($generatedAt, ENT_QUOTES),
            implode('', $htmlItems),
        );

        $output->writeln('Envoi de la notification BD TOPO sur Tchap...');

        try {
            $this->tchap->post($body, $formattedBody);
            $this->logger->info('Notification de mise à jour BD TOPO envoyée sur Tchap');
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l\'envoi de la notification BD TOPO sur Tchap', ['exception' => $e->getMessage()]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param string[] $rawResults
     *
     * @return array<string, string>|null résultats indexés par étape (dans l'ordre de STEPS), ou null si invalides
     */
    private function parseResults(array $rawResults, OutputInterface $output): ?array
    {
        $results = [];

        foreach ($rawResults as $rawResult) {
            [$step, $result] = array_pad(explode('=', $rawResult, 2), 2, '');

            if (!\array_key_exists($step, self::STEPS) || !\in_array($result, self::RESULTS, true) || isset($results[$step])) {
                $output->writeln(\sprintf('<error>Résultat invalide ou dupliqué : "%s"</error>', $rawResult));

                return null;
            }

            $results[$step] = $result;
        }

        $missingSteps = array_diff(array_keys(self::STEPS), array_keys($results));

        if ($missingSteps) {
            $output->writeln(\sprintf('<error>Résultat manquant pour : %s</error>', implode(', ', $missingSteps)));

            return null;
        }

        return array_merge(array_intersect_key(self::STEPS, $results), $results);
    }
}
