<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240306130254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add road line columns to Location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD road_line_geometry geometry(GEOMETRY, 2154) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD road_line_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP road_line_geometry');
        $this->addSql('ALTER TABLE location DROP road_line_id');
    }
}
