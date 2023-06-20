<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230620075741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create VehicleSet table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vehicle_set (uuid UUID NOT NULL, measure_uuid UUID NOT NULL, restricted_types TEXT NOT NULL, other_restricted_type_text VARCHAR(255) DEFAULT NULL, exempted_types TEXT NOT NULL, other_exempted_type_text VARCHAR(255) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C40FC1A96A61612 ON vehicle_set (measure_uuid)');
        $this->addSql('COMMENT ON COLUMN vehicle_set.restricted_types IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN vehicle_set.exempted_types IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE vehicle_set ADD CONSTRAINT FK_7C40FC1A96A61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set DROP CONSTRAINT FK_7C40FC1A96A61612');
        $this->addSql('DROP TABLE vehicle_set');
    }
}
