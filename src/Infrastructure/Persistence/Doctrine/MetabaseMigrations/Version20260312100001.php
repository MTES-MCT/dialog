<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\MetabaseMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create analytics_api_request table for API usage statistics (diffusion vs write, by request date)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS analytics_api_request (
                id UUID NOT NULL,
                uploaded_at TIMESTAMP(0) NOT NULL,
                request_date DATE NOT NULL,
                type VARCHAR(20) NOT NULL,
                count INTEGER NOT NULL,
                PRIMARY KEY(id)
            );',
        );
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_api_request_uploaded_at
            ON analytics_api_request (uploaded_at);',
        );
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_api_request_request_date
            ON analytics_api_request (request_date);',
        );
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_api_request_type
            ON analytics_api_request (type);',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS analytics_api_request');
    }
}
