<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240329102233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user"
                (uuid, full_name, email, password) VALUES
                (\'13ad7a59-bd3c-4e1d-8f85-8b148ad00705\', \'Julien JACQUELINET\', \'julien.jacquelinet@beta.gouv.fr\', \'$2y$13$0vx4Anj.TVy8D0iGrER4zuKT//vYlnEx1bLpoUZnv2lNPmWNWXrQC\')
        ');
        $this->addSQL("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('13ad7a59-bd3c-4e1d-8f85-8b148ad00705', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
