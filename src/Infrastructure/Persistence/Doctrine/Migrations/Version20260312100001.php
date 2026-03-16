<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_usage_daily table (Domain\\Statistics\\ApiUsageDaily) for API call count per day and per type; exported_at marks rows sent to Metabase';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_usage_daily (
            uuid UUID NOT NULL,
            day DATE NOT NULL,
            type VARCHAR(20) NOT NULL,
            count INT NOT NULL DEFAULT 0,
            exported_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
            PRIMARY KEY (uuid),
            CONSTRAINT api_usage_daily_day_type_uniq UNIQUE (day, type),
            CONSTRAINT api_usage_daily_type_check CHECK (type IN (\'cifs\', \'datex\', \'web\'))
        )');
        $this->addSql('CREATE INDEX idx_api_usage_daily_day ON api_usage_daily (day)');
        $this->addSql('CREATE INDEX idx_api_usage_daily_exported_at ON api_usage_daily (exported_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_usage_daily');
    }
}
