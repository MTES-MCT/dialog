<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240318161612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix location coordinate system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SELECT UpdateGeometrySRID(\'location\', \'geometry\', 4326)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SELECT UpdateGeometrySRID(\'location\', \'geometry\', 2154)');
    }
}
