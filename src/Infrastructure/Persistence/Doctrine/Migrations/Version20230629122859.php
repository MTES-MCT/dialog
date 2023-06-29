<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230629122859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO organization (uuid, name) VALUES
                ('30de5316-1ee0-49f6-9137-18ee79f741dd', 'Mairie Enval')
        ");

        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'4418eb23-a469-4495-8e97-213491eca33a\', \'Christian Melis\', \'christian.melis6@orange.fr \', \'$2y$13$6LHB70ECny1I.D4PRpqdBupVikwLtqEUbFjqZ6sdcQfkWZGIIVOPC\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('4418eb23-a469-4495-8e97-213491eca33a', '30de5316-1ee0-49f6-9137-18ee79f741dd')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
