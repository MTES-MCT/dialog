<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\PasswordHasherInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:db:anonymize',
    description: 'Anonymise les données personnelles d\'une base copiée depuis la prod (staging / review-apps).',
    hidden: false,
)]
class AnonymizeDatabaseCommand extends Command
{
    private const DEFAULT_PASSWORD = 'staging-password-reset-me';

    public function __construct(
        private Connection $connection,
        private PasswordHasherInterface $passwordHasher,
        private string $appEnv,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Confirme l\'exécution.')
            ->addOption('allow-prod-env', null, InputOption::VALUE_NONE, 'Autorise l\'exécution même si APP_ENV=prod (DANGER).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnv === 'prod' && !$input->getOption('allow-prod-env')) {
            $io->error('Refus d\'exécuter sur APP_ENV=prod. Utilisez --allow-prod-env si vous savez ce que vous faites.');

            return Command::FAILURE;
        }

        if (!$input->getOption('force')) {
            $io->warning('Cette commande va écraser les données personnelles. Relancez avec --force pour exécuter.');

            return Command::FAILURE;
        }

        // Les super-admins (ROLE_SUPER_ADMIN) sont préservés pour pouvoir se connecter sur staging.
        // La colonne `roles` est sérialisée par Doctrine (type=array), un LIKE suffit pour filtrer.
        $preserveRolesPredicate = "roles NOT LIKE '%ROLE_SUPER_ADMIN%'";

        $io->section('Anonymisation des utilisateurs (hors super-admins)');
        $this->connection->executeStatement(<<<SQL
            UPDATE "user"
            SET
                email = CONCAT('user+', uuid, '@example.invalid'),
                full_name = CONCAT('Utilisateur ', SUBSTRING(uuid::text, 1, 8))
            WHERE {$preserveRolesPredicate}
        SQL);

        $io->section('Réinitialisation des mots de passe (hors super-admins)');
        $hashed = $this->passwordHasher->hash(self::DEFAULT_PASSWORD);
        $this->connection->executeStatement(
            <<<SQL
                UPDATE password_user
                SET password = :hash
                WHERE user_uuid IN (SELECT uuid FROM "user" WHERE {$preserveRolesPredicate})
            SQL,
            ['hash' => $hashed],
        );

        $io->section('Purge des tokens et invitations');
        $this->connection->executeStatement('DELETE FROM token');
        $this->connection->executeStatement('DELETE FROM invitation');

        $io->section('Anonymisation des feedbacks et signalements');
        $this->connection->executeStatement("UPDATE feedback SET content = '[anonymisé]'");
        $this->connection->executeStatement("UPDATE report_address SET content = '[anonymisé]', ign_report_id = NULL");

        $io->section('Anonymisation des mailing lists');
        $this->connection->executeStatement(<<<'SQL'
            UPDATE mailing_list
            SET email = CONCAT('contact+', uuid, '@example.invalid')
        SQL);

        $io->section('Désactivation et rotation des secrets API clients');
        $this->connection->executeStatement(<<<'SQL'
            UPDATE api_client
            SET client_secret = CONCAT('staging-rotated-', uuid),
                is_active = false
        SQL);

        $io->success('Anonymisation terminée. Super-admins préservés. Mot de passe par défaut des autres utilisateurs : ' . self::DEFAULT_PASSWORD);

        return Command::SUCCESS;
    }
}
