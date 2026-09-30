<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add administrative_boundary table: official boundaries (COG) of communes, EPCI, departments and regions, fetched on demand and used to filter restrictions by local authority (#2112)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE administrative_boundary (code_type VARCHAR(15) NOT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(255) NOT NULL, geometry geometry(GEOMETRY, 4326) NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(code_type, code))');
        $this->addSql('COMMENT ON COLUMN administrative_boundary.geometry IS \'(DC2Type:geojson_geometry)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE administrative_boundary');
    }
}
