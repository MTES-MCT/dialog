<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320122522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial BD TOPO configuration (ran manually w/ the main user)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION postgis');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION postgis');
    }
}
