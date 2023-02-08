<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230208101136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'21c58999-d259-486d-8e25-e272f4c554cc\', \'Stéphane SCHULTZ\', \'stephane.schultz@beta.gouv.fr\', \'$2y$13$5YAXUBt0RMmW1sN0rHkIMeEVVwlR1TzXxQ72Wtm/FkGOB54QjYSWm\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('21c58999-d259-486d-8e25-e272f4c554cc', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
