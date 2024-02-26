<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240226105229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'ab94da75-e3f3-4b78-a4af-caba326c7ed1\', \'Angela Lebreton\', \'angela.lebreton@beta.gouv.fr\', \'$2y$13$oyOy13ASPa0r5Y4Gf.IwRuaDA9PAze7aLZRAZz/ULPm0.Rc.XRWY6\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('ab94da75-e3f3-4b78-a4af-caba326c7ed1', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66')
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
