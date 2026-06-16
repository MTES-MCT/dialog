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
use Symfony\Component\String\ByteString;

#[AsCommand(
    name: 'app:db:anonymize',
    description: 'Anonymise les données personnelles d\'une base copiée depuis la prod (staging / review-apps).',
    hidden: false,
)]
class AnonymizeDatabaseCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private PasswordHasherInterface $passwordHasher,
        private bool $isProd,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Confirme l\'exécution.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->isProd) {
            $io->warning('Vous êtes sur la production (IS_PROD=true). Cette commande ne doit pas être exécutée car elle va altérer les données.');

            return Command::FAILURE;
        }

        if (!$input->getOption('force')) {
            $io->warning('Cette commande va écraser les données personnelles. Relancez avec --force pour exécuter.');

            return Command::FAILURE;
        }

        // Les super-admins (ROLE_SUPER_ADMIN) sont préservés pour pouvoir se connecter les environnements de staging.
        $io->section('Réinitialisation des mots de passe (hors super-admins)');
        $generatedPassword = ByteString::fromRandom(32)->toString();
        $hashed = $this->passwordHasher->hash($generatedPassword);
        $this->connection->executeStatement(
            <<<SQL
                UPDATE password_user
                SET password = :hash
                WHERE user_uuid IN (SELECT uuid FROM "public"."user" WHERE roles NOT LIKE '%ROLE_SUPER_ADMIN%')
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

        $io->success('Anonymisation terminée. Super-admins préservés. Mot de passe généré pour les autres utilisateurs : ' . $generatedPassword);

        return Command::SUCCESS;
    }
}
