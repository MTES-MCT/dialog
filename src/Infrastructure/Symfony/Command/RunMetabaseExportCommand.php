<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:metabase:export',
    description: 'Export indicators to Metabase',
    hidden: false,
)]
class RunMetabaseExportCommand extends Command
{
    public function __construct(
        private DateUtilsInterface $dateUtils,
        private Connection $connection,
        private Connection $metabaseConnection,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->dateUtils->getNow();

        $this->exportActiveUsers($now);

        return Command::SUCCESS;
    }

    private function exportActiveUsers(\DateTimeInterface $now): void
    {
        // À chaque exécution, on ajoute la liste des dates de dernière activité pour chaque utilisateur, et la date d'exécution.
        // Dans Metabase cela permet de calculer le nombre d'utilisateurs actif au moment de chaque exécution.
        // (Par exemple avec un filtre : "[last_active_at] >= [uploaded_at] - 7 jours", puis en groupant sur le uploaded_at.)
        $this->metabaseConnection->executeQuery(
            'CREATE TABLE IF NOT EXISTS analytics_user_active (id UUID NOT NULL, uploaded_at TIMESTAMP(0), last_active_at TIMESTAMP(0), PRIMARY KEY(id));',
        );
        $this->metabaseConnection->executeQuery(
            'CREATE INDEX IF NOT EXISTS idx_analytics_user_active_uploaded_at ON analytics_user_active (uploaded_at);',
        );

        $userRows = $this->connection->fetchAllAssociative(
            'SELECT uuid_generate_v4() AS id, last_active_at FROM "user"',
        );

        $stmt = $this->metabaseConnection->prepare('INSERT INTO analytics_user_active(id, uploaded_at, last_active_at) VALUES (:id, (:uploaded_at)::timestamp(0), :last_active_at)');

        foreach ($userRows as $row) {
            $stmt->bindValue('id', $row['id']);
            $stmt->bindValue('uploaded_at', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('last_active_at', $row['last_active_at']);
            $stmt->execute();
        }
    }
}
