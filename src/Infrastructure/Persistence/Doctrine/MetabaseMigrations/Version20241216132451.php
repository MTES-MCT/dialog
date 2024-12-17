<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\MetabaseMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241216132451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS analytics_user_active (
                id UUID NOT NULL,
                uploaded_at TIMESTAMP(0),
                last_active_at TIMESTAMP(0),
                PRIMARY KEY(id)
            );',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_user_active_uploaded_at
            ON analytics_user_active (uploaded_at);',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS analytics_user_active');
    }
}
