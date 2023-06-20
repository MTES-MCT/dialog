<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230620144428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE vehicle_characteristics');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vehicle_characteristics (uuid UUID NOT NULL, vehicle_type VARCHAR(40) DEFAULT NULL, vehicle_usage VARCHAR(30) DEFAULT NULL, vehicle_critair VARCHAR(2) DEFAULT NULL, max_weight DOUBLE PRECISION DEFAULT NULL, max_height DOUBLE PRECISION DEFAULT NULL, max_width DOUBLE PRECISION DEFAULT NULL, max_length DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_weight IS \'Unit in tonnes.\'');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_height IS \'Unit in meters.\'');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_width IS \'Unit in meters.\'');
        $this->addSql('COMMENT ON COLUMN vehicle_characteristics.max_length IS \'Unit in meters.\'');
    }
}
