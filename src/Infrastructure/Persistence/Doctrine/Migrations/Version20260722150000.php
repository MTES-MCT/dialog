<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ign_geometry (WKT) to report_address to allow a later manual submission to IGN';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_address ADD ign_geometry TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_address DROP ign_geometry');
    }
}
