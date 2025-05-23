<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250507085352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration initiale pour la BD TOPO';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION IF EXISTS postgis');
    }
}
