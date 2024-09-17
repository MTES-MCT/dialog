<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240917103338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Install postgis_sfcgal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis_sfcgal');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION IF EXISTS postgis_sfcgal');
    }
}
