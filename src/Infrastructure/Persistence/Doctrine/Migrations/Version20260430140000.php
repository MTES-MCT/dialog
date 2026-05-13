<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add top_published_organization cache table (top 10 organizations by number of published regulation orders, with bbox), refreshed daily by a cron command.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE top_published_organization (
            organization_uuid UUID NOT NULL,
            nb_published INTEGER NOT NULL,
            min_lon DOUBLE PRECISION NOT NULL,
            min_lat DOUBLE PRECISION NOT NULL,
            max_lon DOUBLE PRECISION NOT NULL,
            max_lat DOUBLE PRECISION NOT NULL,
            refreshed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (organization_uuid)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE top_published_organization');
    }
}
