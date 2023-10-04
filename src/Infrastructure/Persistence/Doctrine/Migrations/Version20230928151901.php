<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230928151901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE vehicle_set SET exempted_types = replace(exempted_types, 's:9:\"emergencyServices\"','s:17:\"emergencyServices\"') WHERE exempted_types LIKE '%emergencyServices%'");
        $this->addSql("UPDATE vehicle_set SET exempted_types = replace(exempted_types, 's:3:\"commercial\"','s:10:\"commercial\"') WHERE exempted_types LIKE '%commercial%'");
    }

    public function down(Schema $schema): void
    {
    }
}
