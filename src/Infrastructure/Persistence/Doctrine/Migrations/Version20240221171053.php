<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240221171053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename location_new to location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_new RENAME TO location');
        $this->addSql('ALTER INDEX idx_a31cfd096a61612 RENAME TO IDX_5E9E89CB96A61612');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location RENAME TO location_new');
        $this->addSql('ALTER INDEX IDX_5E9E89CB96A61612 RENAME TO idx_a31cfd096a61612');
    }
}
