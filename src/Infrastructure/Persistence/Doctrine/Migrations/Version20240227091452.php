<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240227091452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM "organizations_users" WHERE user_uuid = \'ab94da75-e3f3-4b78-a4af-caba326c7ed1\'');
        $this->addSql('DELETE FROM "user" WHERE uuid = \'ab94da75-e3f3-4b78-a4af-caba326c7ed1\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
