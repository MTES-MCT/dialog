<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230703094602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'ca9b1e1e-d33f-4b42-aabf-869afdd7d557\', \'Ariane Berthellemy\', \'ariane.berthellemy@efrei.net\', \'$2y$13$W9ioA7iycSIWZ4DTeE67ZOd1SaWh7k5kkaj8XXbeSfkjfuDkMqIqO\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('ca9b1e1e-d33f-4b42-aabf-869afdd7d557', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'),
                ('ca9b1e1e-d33f-4b42-aabf-869afdd7d557', 'a41e2e19-51b4-4f6d-8a5a-abb68f0ef648')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
