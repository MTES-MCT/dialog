<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add whole_city and whole_city_exception tables for "ville entière" location type with exceptions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE whole_city (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, city_code VARCHAR(5) NOT NULL, city_label VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_WHOLE_CITY_LOCATION ON whole_city (location_uuid)');
        $this->addSql('ALTER TABLE whole_city ADD CONSTRAINT FK_WHOLE_CITY_LOCATION FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE whole_city_exception (uuid UUID NOT NULL, whole_city_uuid UUID DEFAULT NULL, road_ban_id VARCHAR(20) NOT NULL, road_name VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_WHOLE_CITY_EXCEPTION_WHOLE_CITY ON whole_city_exception (whole_city_uuid)');
        $this->addSql('ALTER TABLE whole_city_exception ADD CONSTRAINT FK_WHOLE_CITY_EXCEPTION_WHOLE_CITY FOREIGN KEY (whole_city_uuid) REFERENCES whole_city (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE whole_city_exception DROP CONSTRAINT FK_WHOLE_CITY_EXCEPTION_WHOLE_CITY');
        $this->addSql('DROP TABLE whole_city_exception');
        $this->addSql('ALTER TABLE whole_city DROP CONSTRAINT FK_WHOLE_CITY_LOCATION');
        $this->addSql('DROP TABLE whole_city');
    }
}
