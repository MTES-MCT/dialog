<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240108145118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set RENAME COLUMN heavyweight_max_width TO max_width');
        $this->addSql('ALTER TABLE vehicle_set RENAME COLUMN heavyweight_max_length TO max_length');
        $this->addSql('ALTER TABLE vehicle_set RENAME COLUMN heavyweight_max_height TO max_height');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set RENAME COLUMN max_width TO heavyweight_max_width');
        $this->addSql('ALTER TABLE vehicle_set RENAME COLUMN max_length TO heavyweight_max_length');
        $this->addSql('ALTER TABLE vehicle_set RENAME COLUMN max_height TO heavyweight_max_height');
    }
}
