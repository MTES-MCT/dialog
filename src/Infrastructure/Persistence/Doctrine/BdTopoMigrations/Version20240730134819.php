<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240730134819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on troncon_de_route.nature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS troncon_de_route_nature_idx ON troncon_de_route (nature);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS troncon_de_route_nature_idx');
    }
}
