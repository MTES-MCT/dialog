<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\StringUtilsInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:anct:import',
    description: 'Importe la liste des adresses e-mail officielles des collectivités (DPNT de l\'ANCT) dans anct_territory_contact',
)]
class AnctImportCommand extends Command
{
    private const DEFAULT_URL = 'https://www.data.gouv.fr/api/1/datasets/r/fd73a12f-572c-4b04-89e9-91cc8c6ebcb3';
    private const BATCH_SIZE = 1000;

    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private StringUtilsInterface $stringUtils,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'URL du fichier dpnt-quotidien.json.gz', self::DEFAULT_URL)
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin local vers un fichier json ou json.gz (ignore --url)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getOption('file');
        if ($file !== null) {
            $io->text(\sprintf('Lecture du fichier local %s', $file));
            $raw = file_get_contents($file);
            if ($raw === false) {
                $io->error(\sprintf('Impossible de lire le fichier %s', $file));

                return Command::FAILURE;
            }
        } else {
            $url = $input->getOption('url');
            $io->text(\sprintf('Téléchargement depuis %s', $url));
            $raw = $this->httpClient->request('GET', $url)->getContent();
        }

        $json = $this->decompressIfNeeded($raw);
        $territories = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        $rows = [];
        foreach ($territories as $territory) {
            $siret = $territory['siret'] ?? null;
            $email = $territory['adresse_messagerie'] ?? null;

            if (!\is_string($siret) || $siret === '' || !\is_string($email) || $email === '') {
                continue;
            }

            $rows[$siret] = $this->stringUtils->normalizeEmail($email);
        }

        $io->text(\sprintf('%d collectivités avec une adresse e-mail officielle', \count($rows)));

        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $connection->executeStatement('TRUNCATE TABLE anct_territory_contact');
            $this->insertRows($connection, $rows);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $io->error(\sprintf('Import interrompu : %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(\sprintf('%d adresses e-mail importées dans anct_territory_contact', \count($rows)));

        return Command::SUCCESS;
    }

    private function decompressIfNeeded(string $raw): string
    {
        if (\strlen($raw) >= 2 && "\x1f\x8b" === substr($raw, 0, 2)) {
            $decoded = gzdecode($raw);
            if ($decoded === false) {
                throw new \RuntimeException('Impossible de décompresser le fichier gzip.');
            }

            return $decoded;
        }

        return $raw;
    }

    /**
     * @param array<string, string> $rows
     */
    private function insertRows(Connection $connection, array $rows): void
    {
        $batch = [];
        foreach ($rows as $siret => $email) {
            $batch[] = [$siret, $email];

            if (\count($batch) >= self::BATCH_SIZE) {
                $this->insertBatch($connection, $batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->insertBatch($connection, $batch);
        }
    }

    /**
     * @param list<array{0: string, 1: string}> $batch
     */
    private function insertBatch(Connection $connection, array $batch): void
    {
        $placeholders = implode(', ', array_fill(0, \count($batch), '(?, ?)'));
        $params = [];
        foreach ($batch as $row) {
            $params[] = $row[0];
            $params[] = $row[1];
        }

        $connection->executeStatement(
            \sprintf(
                'INSERT INTO anct_territory_contact (siret, email) VALUES %s ON CONFLICT (siret) DO UPDATE SET email = EXCLUDED.email',
                $placeholders,
            ),
            $params,
        );
    }
}
