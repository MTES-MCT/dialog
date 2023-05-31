<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230619125319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add measure vehicle columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE measure ADD restricted_vehicle_types TEXT');
        $this->addSql(sprintf('UPDATE measure SET restricted_vehicle_types = \'%s\'', serialize([])));
        $this->addSql('ALTER TABLE measure ALTER restricted_vehicle_types SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN measure.restricted_vehicle_types IS \'(DC2Type:array)\'');

        $this->addSql('ALTER TABLE measure ADD other_restricted_vehicle_type_text VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE measure ADD exempted_vehicle_types TEXT');
        $this->addSql(sprintf('UPDATE measure SET exempted_vehicle_types = \'%s\'', serialize([])));
        $this->addSql('ALTER TABLE measure ALTER exempted_vehicle_types SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN measure.exempted_vehicle_types IS \'(DC2Type:array)\'');

        $this->addSql('ALTER TABLE measure ADD other_exempted_vehicle_type_text VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE measure DROP restricted_vehicle_types');
        $this->addSql('ALTER TABLE measure DROP other_restricted_vehicle_type_text');
        $this->addSql('ALTER TABLE measure DROP exempted_vehicle_types');
        $this->addSql('ALTER TABLE measure DROP other_exempted_vehicle_type_text');
    }
}
