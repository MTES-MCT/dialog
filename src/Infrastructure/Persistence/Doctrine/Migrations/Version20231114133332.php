<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231114133332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD geometry geometry(GEOMETRY, 2154) DEFAULT NULL');
        $this->addSql('UPDATE location SET geometry = ST_MakeLine(from_point, to_point);');
        // from_point and to_point will be dropped later
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP geometry');
    }
}
