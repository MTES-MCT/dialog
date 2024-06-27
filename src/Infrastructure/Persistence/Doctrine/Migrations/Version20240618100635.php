<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240618100635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add RawGeoJSON entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE raw_geojson (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC218CA1517BE5E6 ON raw_geojson (location_uuid)');
        $this->addSql('ALTER TABLE raw_geojson ADD CONSTRAINT FK_AC218CA1517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE raw_geojson DROP CONSTRAINT FK_AC218CA1517BE5E6');
        $this->addSql('DROP TABLE raw_geojson');
    }
}
