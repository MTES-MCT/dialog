<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618082916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority DROP IF EXISTS road_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority DROP IF EXISTS city_code
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority DROP IF EXISTS city_label
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority DROP IF EXISTS address
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority RENAME COLUMN place_of_signature TO role
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority ADD road_name VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority ADD city_code VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority ADD city_label VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority ADD address VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signing_authority RENAME COLUMN role TO place_of_signature
        SQL);
    }
}
