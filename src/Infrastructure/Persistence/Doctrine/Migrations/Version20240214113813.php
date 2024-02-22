<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240214113813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP CONSTRAINT fk_5e9e89cb267e0d5e');
        $this->addSql('DROP TABLE location');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE location (uuid UUID NOT NULL, regulation_order_uuid UUID DEFAULT NULL, from_house_number VARCHAR(8) DEFAULT NULL, from_point geometry(POINT, 2154) DEFAULT NULL, to_house_number VARCHAR(8) DEFAULT NULL, to_point geometry(POINT, 2154) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, geometry geometry(GEOMETRY, 2154) DEFAULT NULL, city_code VARCHAR(5) DEFAULT NULL, road_name VARCHAR(255) DEFAULT NULL, city_label VARCHAR(255) DEFAULT NULL, road_type VARCHAR(40) NOT NULL, administrator VARCHAR(255) DEFAULT NULL, road_number VARCHAR(50) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_5e9e89cb267e0d5e ON location (regulation_order_uuid)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT fk_5e9e89cb267e0d5e FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
