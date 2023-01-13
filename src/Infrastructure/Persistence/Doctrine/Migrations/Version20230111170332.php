<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230111170332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis');
        $this->addSql('CREATE TABLE location (uuid UUID NOT NULL, regulation_condition_uuid UUID NOT NULL, postal_code VARCHAR(5) NOT NULL, city VARCHAR(255) NOT NULL, road_name VARCHAR(60) NOT NULL, from_house_number VARCHAR(8) NOT NULL, from_point geometry(POINT, 2154) NOT NULL, to_house_number VARCHAR(8) NOT NULL, to_point geometry(POINT, 2154) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E9E89CB9F073263 ON location (regulation_condition_uuid)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB9F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB9F073263');
        $this->addSql('DROP TABLE location');
    }
}
