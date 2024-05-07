<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240423134318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE named_street ADD from_road_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE named_street ADD to_road_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE named_street DROP from_road_name');
        $this->addSql('ALTER TABLE named_street DROP to_road_name');
    }
}
