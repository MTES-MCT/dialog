<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\MetabaseMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241216142341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS analytics_count (
                id UUID NOT NULL,
                uploaded_at TIMESTAMP(0),
                name VARCHAR(32),
                value INTEGER,
                PRIMARY KEY(id)
            );',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_count_uploaded_at
            ON analytics_count (uploaded_at);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_count_name
            ON analytics_count (name);',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE analytics_count');
    }
}
