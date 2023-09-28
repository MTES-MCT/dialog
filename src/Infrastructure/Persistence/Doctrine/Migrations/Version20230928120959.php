<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230928120959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE vehicle_set SET exempted_types = replace(exempted_types, 'ambulance','emergencyServices') WHERE exempted_types LIKE '%ambulance%'");
        $this->addSql("UPDATE vehicle_set SET exempted_types = replace(exempted_types, 'bus','commercial') WHERE exempted_types LIKE '%bus%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE vehicle_set SET exempted_types = replace(exempted_types, 'emergencyServices','ambulance') WHERE exempted_types LIKE '%emergencyServices%'");
        $this->addSql("UPDATE vehicle_set SET exempted_types = replace(exempted_types, 'commercial','bus') WHERE exempted_types LIKE '%commercial%'");
    }
}
