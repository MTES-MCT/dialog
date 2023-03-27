<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230322155915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE regulation_order ADD identifier VARCHAR(60) DEFAULT NULL');
        $this->addSql('UPDATE regulation_order SET identifier = \'N/C\'');
        $this->addSql('ALTER TABLE regulation_order ALTER identifier SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order DROP issuing_authority');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE regulation_order ADD issuing_authority VARCHAR(255)');
        $this->addSql('UPDATE regulation_order SET issuing_authority = \'N/C\'');
        $this->addSql('ALTER TABLE regulation_order ALTER COLUMN issuing_authority SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order DROP identifier');
    }
}
