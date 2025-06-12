<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527132322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE storage_regulation_order (uuid UUID NOT NULL, regulation_order_uuid UUID NOT NULL, path VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(uuid))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_24CDE2BE267E0D5E ON storage_regulation_order (regulation_order_uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE storage_regulation_order ADD CONSTRAINT FK_24CDE2BE267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE storage_regulation_order DROP CONSTRAINT FK_24CDE2BE267E0D5E
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE storage_regulation_order
        SQL);
    }
}
