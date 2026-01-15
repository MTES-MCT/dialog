<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115161436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dailyrange ALTER applicable_days TYPE JSON USING applicable_days::json');
        $this->addSql('COMMENT ON COLUMN dailyrange.applicable_days IS \'\'');
        $this->addSql('ALTER TABLE location ALTER geometry TYPE geometry(GEOMETRY, 4326)');
        $this->addSql('COMMENT ON COLUMN location.geometry IS \'\'');
        $this->addSql('ALTER TABLE organization ALTER geometry TYPE geometry(GEOMETRY, 4326)');
        $this->addSql('COMMENT ON COLUMN organization.geometry IS \'\'');
        $this->addSql('ALTER TABLE organizations_users ALTER roles TYPE JSON USING roles::json');
        $this->addSql('COMMENT ON COLUMN organizations_users.roles IS \'\'');
        $this->addSql('ALTER TABLE storage_area ALTER geometry TYPE geometry(GEOMETRY, 4326)');
        $this->addSql('COMMENT ON COLUMN storage_area.geometry IS \'\'');
        $this->addSql('ALTER TABLE "user" ALTER roles TYPE JSON USING roles::json');
        $this->addSql('COMMENT ON COLUMN "user".roles IS \'\'');
        $this->addSql('ALTER TABLE vehicle_set ALTER restricted_types TYPE JSON USING restricted_types::json');
        $this->addSql('ALTER TABLE vehicle_set ALTER exempted_types TYPE JSON USING exempted_types::json');
        $this->addSql('ALTER TABLE vehicle_set ALTER critair_types TYPE JSON USING critair_types::json');
        $this->addSql('COMMENT ON COLUMN vehicle_set.restricted_types IS \'\'');
        $this->addSql('COMMENT ON COLUMN vehicle_set.exempted_types IS \'\'');
        $this->addSql('COMMENT ON COLUMN vehicle_set.critair_types IS \'\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE dailyrange ALTER applicable_days TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN dailyrange.applicable_days IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE location ALTER geometry TYPE geometry(GEOMETRY, 0)');
        $this->addSql('COMMENT ON COLUMN location.geometry IS \'(DC2Type:geojson_geometry)\'');
        $this->addSql('ALTER TABLE organization ALTER geometry TYPE geometry(GEOMETRY, 0)');
        $this->addSql('COMMENT ON COLUMN organization.geometry IS \'(DC2Type:geojson_geometry)\'');
        $this->addSql('ALTER TABLE organizations_users ALTER roles TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN organizations_users.roles IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE storage_area ALTER geometry TYPE geometry(GEOMETRY, 0)');
        $this->addSql('COMMENT ON COLUMN storage_area.geometry IS \'(DC2Type:geojson_geometry)\'');
        $this->addSql('ALTER TABLE "user" ALTER roles TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN "user".roles IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE vehicle_set ALTER restricted_types TYPE TEXT');
        $this->addSql('ALTER TABLE vehicle_set ALTER critair_types TYPE TEXT');
        $this->addSql('ALTER TABLE vehicle_set ALTER exempted_types TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN vehicle_set.restricted_types IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN vehicle_set.critair_types IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN vehicle_set.exempted_types IS \'(DC2Type:array)\'');
    }
}
