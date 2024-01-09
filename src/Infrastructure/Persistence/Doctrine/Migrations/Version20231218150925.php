<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231218150925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE location_new (uuid UUID NOT NULL, measure_uuid UUID DEFAULT NULL, city_code VARCHAR(5) NOT NULL, city_label VARCHAR(255) NOT NULL, road_name VARCHAR(255) DEFAULT NULL, from_house_number VARCHAR(8) DEFAULT NULL, to_house_number VARCHAR(8) DEFAULT NULL, geometry geometry(GEOMETRY, 2154) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_A31CFD096A61612 ON location_new (measure_uuid)');
        $this->addSql('ALTER TABLE location_new ADD CONSTRAINT FK_A31CFD096A61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location_new DROP CONSTRAINT FK_A31CFD096A61612');
        $this->addSql('DROP TABLE location_new');
    }
}
