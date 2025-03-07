<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306085340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization ADD updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD code VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD code_type VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD geometry geometry(GEOMETRY, 4326) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organization.geometry IS \'(DC2Type:geojson_geometry)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP updated_at');
        $this->addSql('ALTER TABLE organization DROP code');
        $this->addSql('ALTER TABLE organization DROP code_type');
        $this->addSql('ALTER TABLE organization DROP geometry');
    }
}
