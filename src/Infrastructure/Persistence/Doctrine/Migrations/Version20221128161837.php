<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221128161837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_af4a2c659f073263');
        $this->addSql('ALTER TABLE condition_set ALTER regulation_condition_uuid SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AF4A2C659F073263 ON condition_set (regulation_condition_uuid)');
        $this->addSql('DROP INDEX uniq_9d8762b7299ea18a');
        $this->addSql('CREATE INDEX IDX_9D8762B7299EA18A ON regulation_condition (parent_condition_set_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_AF4A2C659F073263');
        $this->addSql('ALTER TABLE condition_set ALTER regulation_condition_uuid DROP NOT NULL');
        $this->addSql('CREATE INDEX idx_af4a2c659f073263 ON condition_set (regulation_condition_uuid)');
        $this->addSql('DROP INDEX IDX_9D8762B7299EA18A');
        $this->addSql('CREATE UNIQUE INDEX uniq_9d8762b7299ea18a ON regulation_condition (parent_condition_set_uuid)');
    }
}
