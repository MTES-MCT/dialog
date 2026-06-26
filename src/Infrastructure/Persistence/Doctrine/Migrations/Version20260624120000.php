<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add whole_city_exception table: streets excluded from a "ville entière" restriction.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE whole_city_exception (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, road_type VARCHAR(40) NOT NULL, label VARCHAR(255) NOT NULL, geometry geometry(GEOMETRY, 4326) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql("COMMENT ON COLUMN whole_city_exception.geometry IS '(DC2Type:geojson_geometry)'");
        $this->addSql('CREATE INDEX IDX_27182E79517BE5E6 ON whole_city_exception (location_uuid)');
        $this->addSql('ALTER TABLE whole_city_exception ADD CONSTRAINT FK_WHOLE_CITY_EXCEPTION_LOCATION FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE whole_city_exception DROP CONSTRAINT FK_WHOLE_CITY_EXCEPTION_LOCATION');
        $this->addSql('DROP TABLE whole_city_exception');
    }
}
