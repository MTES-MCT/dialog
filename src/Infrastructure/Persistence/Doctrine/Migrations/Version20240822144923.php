<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240822144923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // pg_trgm allows PostgreSQL to use index for queries such as "(I)LIKE '%foo%'"
        // See: https://www.postgresql.org/docs/current/pgtrgm.html#PGTRGM-INDEX
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_regulation_order_identifier ON regulation_order USING gist (identifier gist_trgm_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_regulation_order_identifier');
    }
}
