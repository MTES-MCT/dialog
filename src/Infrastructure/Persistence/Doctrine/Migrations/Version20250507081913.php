<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250507081913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE named_street ADD road_ban_id VARCHAR(20) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE named_street ADD from_road_ban_id VARCHAR(20) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE named_street ADD to_road_ban_id VARCHAR(20) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D9113ECE8496CB65 ON named_street (road_ban_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D9113ECE8496CB65
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE named_street DROP from_road_ban_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE named_street DROP to_road_ban_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE named_street DROP road_ban_id
        SQL);
    }
}
