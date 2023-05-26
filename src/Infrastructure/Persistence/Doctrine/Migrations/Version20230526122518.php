<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526122518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO organization (uuid, name) VALUES ('e8c4f421-e238-4f98-b446-de8aa62feea7', 'Mairie de Saint-Rome')
        ");

        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'fb76d2b9-2203-47bc-ab7b-faae8606d4f0\', \'Joëlle Guillaume\', \'secretariat@mairie-saintrome.fr\', \'$2y$13$h4UZjmMznxyiTpzLzWN6DexV9f8Nr/6bGBPuwRk19l6vQphWm.Y42\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('fb76d2b9-2203-47bc-ab7b-faae8606d4f0', 'e8c4f421-e238-4f98-b446-de8aa62feea7')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
