<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240422085504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE named_street (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, city_code VARCHAR(5) DEFAULT NULL, city_label VARCHAR(255) DEFAULT NULL, road_name VARCHAR(255) DEFAULT NULL, from_house_number VARCHAR(8) DEFAULT NULL, to_house_number VARCHAR(8) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DF07E54E517BE5E6 ON named_street (location_uuid)');
        $this->addSql('CREATE TABLE numbered_road (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, administrator VARCHAR(255) DEFAULT NULL, road_number VARCHAR(50) DEFAULT NULL, from_point_number VARCHAR(5) DEFAULT NULL, from_side VARCHAR(1) DEFAULT NULL, from_abscissa INT DEFAULT 0, to_point_number VARCHAR(5) DEFAULT NULL, to_side VARCHAR(1) DEFAULT NULL, to_abscissa INT DEFAULT 0, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_95C0C4B1517BE5E6 ON numbered_road (location_uuid)');
        $this->addSql('ALTER TABLE named_street ADD CONSTRAINT FK_DF07E54E517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE numbered_road ADD CONSTRAINT FK_95C0C4B1517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('
            INSERT INTO named_street (uuid, location_uuid, city_label, road_name, city_code, from_house_number, to_house_number)
            SELECT public.uuid_generate_v4(), l.uuid, l.city_label, l.road_name, l.city_code, l.from_house_number, l.to_house_number
            FROM location AS l
            WHERE l.road_type = \'lane\'
        ');

        $this->addSql('
            INSERT INTO numbered_road (uuid, location_uuid, administrator, road_number, from_abscissa, from_side, from_point_number, to_abscissa, to_side, to_point_number)
            SELECT public.uuid_generate_v4(), l.uuid, l.administrator, l.road_number, l.from_abscissa, l.from_side, l.from_point_number, l.to_abscissa, l.to_side, l.to_point_number
            FROM location AS l
            WHERE l.road_type = \'departmentalRoad\'
        ');

        $this->addSql('ALTER TABLE location DROP city_code');
        $this->addSql('ALTER TABLE location DROP city_label');
        $this->addSql('ALTER TABLE location DROP road_name');
        $this->addSql('ALTER TABLE location DROP from_house_number');
        $this->addSql('ALTER TABLE location DROP to_house_number');
        $this->addSql('ALTER TABLE location DROP administrator');
        $this->addSql('ALTER TABLE location DROP road_number');
        $this->addSql('ALTER TABLE location DROP from_point_number');
        $this->addSql('ALTER TABLE location DROP from_side');
        $this->addSql('ALTER TABLE location DROP from_abscissa');
        $this->addSql('ALTER TABLE location DROP to_point_number');
        $this->addSql('ALTER TABLE location DROP to_side');
        $this->addSql('ALTER TABLE location DROP to_abscissa');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE named_street DROP CONSTRAINT FK_DF07E54E517BE5E6');
        $this->addSql('ALTER TABLE numbered_road DROP CONSTRAINT FK_95C0C4B1517BE5E6');
        $this->addSql('DROP TABLE named_street');
        $this->addSql('DROP TABLE numbered_road');
        $this->addSql('ALTER TABLE location ADD city_code VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD city_label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD road_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD from_house_number VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD to_house_number VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD administrator VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD road_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD from_point_number VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD from_side VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD from_abscissa INT DEFAULT 0');
        $this->addSql('ALTER TABLE location ADD to_point_number VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD to_side VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD to_abscissa INT DEFAULT 0');
    }
}
