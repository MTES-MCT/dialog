<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230227095236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'364ff7fc-d741-4d8c-96a8-759241d38995\', \'Léa LEFOULON\', \'lea.lefoulon@beta.gouv.fr\', \'$2y$13$LMwOm9aJ3uuWi0Pi/1zC2uVQw677ZduXOx6yys9r2XHcF4xuqWJO6\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('364ff7fc-d741-4d8c-96a8-759241d38995', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
