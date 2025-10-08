<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605131347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE establishment (uuid UUID NOT NULL, organization_uuid UUID NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(6) NOT NULL, city VARCHAR(100) NOT NULL, address_complement VARCHAR(255) DEFAULT NULL, PRIMARY KEY(uuid))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_DBEFB1EEE8766E3B ON establishment (organization_uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE establishment ADD CONSTRAINT FK_DBEFB1EEE8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE establishment DROP CONSTRAINT FK_DBEFB1EEE8766E3B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE establishment
        SQL);
    }
}
