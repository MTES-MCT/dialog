<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202141600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_address RENAME COLUMN road_type TO location');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_address RENAME COLUMN location TO road_type');
    }
}
