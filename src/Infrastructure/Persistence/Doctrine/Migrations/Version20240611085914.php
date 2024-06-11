<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240611085914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE regulation_order_issue DROP CONSTRAINT fk_a13c584de8766e3b');
        $this->addSql('DROP TABLE regulation_order_issue');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE regulation_order_issue (uuid UUID NOT NULL, organization_uuid UUID NOT NULL, identifier VARCHAR(60) NOT NULL, level VARCHAR(10) NOT NULL, source VARCHAR(32) DEFAULT \'dialog\' NOT NULL, context TEXT NOT NULL, geometry geometry(GEOMETRY, 0) DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_a13c584d772e836a ON regulation_order_issue (identifier)');
        $this->addSql('CREATE INDEX idx_a13c584de8766e3b ON regulation_order_issue (organization_uuid)');
        $this->addSql('COMMENT ON COLUMN regulation_order_issue.geometry IS \'(DC2Type:geojson_geometry)\'');
        $this->addSql('ALTER TABLE regulation_order_issue ADD CONSTRAINT fk_a13c584de8766e3b FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
