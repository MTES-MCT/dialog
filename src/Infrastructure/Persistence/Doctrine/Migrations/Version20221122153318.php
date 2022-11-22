<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221122153318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE regulation_order (uuid UUID NOT NULL, description TEXT NOT NULL, issuing_authority VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE TABLE regulation_order_record (uuid UUID NOT NULL, regulation_order_uuid UUID NOT NULL, organization_uuid UUID NOT NULL, status VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_528B2E6C267E0D5E ON regulation_order_record (regulation_order_uuid)');
        $this->addSql('CREATE INDEX IDX_528B2E6CE8766E3B ON regulation_order_record (organization_uuid)');
        $this->addSql('COMMENT ON COLUMN regulation_order_record.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE regulation_order_record ADD CONSTRAINT FK_528B2E6C267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_order_record ADD CONSTRAINT FK_528B2E6CE8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_order_record DROP CONSTRAINT FK_528B2E6C267E0D5E');
        $this->addSql('ALTER TABLE regulation_order_record DROP CONSTRAINT FK_528B2E6CE8766E3B');
        $this->addSql('DROP TABLE regulation_order');
        $this->addSql('DROP TABLE regulation_order_record');
    }
}
