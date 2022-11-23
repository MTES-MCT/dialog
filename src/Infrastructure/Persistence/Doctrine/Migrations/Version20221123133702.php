<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221123133702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vehicle_characteristics (uuid UUID NOT NULL, vehicle_type VARCHAR(40) DEFAULT NULL, vehicle_usage VARCHAR(30) DEFAULT NULL, vehicle_critair VARCHAR(2) DEFAULT NULL, max_weight DOUBLE PRECISION DEFAULT NULL, max_height DOUBLE PRECISION DEFAULT NULL, max_width DOUBLE PRECISION DEFAULT NULL, max_length DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_weight IS \'Unit in tonnes.\'');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_height IS \'Unit in meters.\'');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_width IS \'Unit in meters.\'');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_length IS \'Unit in meters.\'');
        $this->addSql('ALTER TABLE regulation_condition ADD vehicle_characteristics_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT FK_9D8762B7AAEE03D4 FOREIGN KEY (vehicle_characteristics_uuid) REFERENCES vehicle_characteristics (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D8762B7AAEE03D4 ON regulation_condition (vehicle_characteristics_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT FK_9D8762B7AAEE03D4');
        $this->addSql('DROP TABLE vehicle_characteristics');
        $this->addSql('DROP INDEX UNIQ_9D8762B7AAEE03D4');
        $this->addSql('ALTER TABLE regulation_condition DROP vehicle_characteristics_uuid');
    }
}
