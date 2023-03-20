<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230321085532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD regulation_condition_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE9F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT fk_9d8762b7299ea18a');
        $this->addSql('ALTER TABLE condition_set DROP CONSTRAINT fk_af4a2c659f073263');
        $this->addSql('DROP TABLE condition_set');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_C5B81ECE9F073263 ON period (regulation_condition_uuid)');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT fk_9d8762b7267e0d5e');
        $this->addSql('DROP INDEX uniq_9d8762b7267e0d5e');
        $this->addSql('DROP INDEX idx_9d8762b7299ea18a');
        $this->addSql('ALTER TABLE regulation_condition DROP parent_condition_set_uuid');
        $this->addSql('ALTER TABLE regulation_condition DROP regulation_order_uuid');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE condition_set (uuid UUID NOT NULL, regulation_condition_uuid UUID NOT NULL, operator VARCHAR(5) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX uniq_af4a2c659f073263 ON condition_set (regulation_condition_uuid)');
        $this->addSql('ALTER TABLE condition_set ADD CONSTRAINT fk_af4a2c659f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE9F073263');
        $this->addSql('DROP INDEX UNIQ_C5B81ECE9F073263');
        $this->addSql('ALTER TABLE period DROP regulation_condition_uuid');
        $this->addSql('ALTER TABLE regulation_condition ADD parent_condition_set_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD regulation_order_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT fk_9d8762b7299ea18a FOREIGN KEY (parent_condition_set_uuid) REFERENCES condition_set (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT fk_9d8762b7267e0d5e FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_9d8762b7267e0d5e ON regulation_condition (regulation_order_uuid)');
        $this->addSql('CREATE INDEX idx_9d8762b7299ea18a ON regulation_condition (parent_condition_set_uuid)');
    }
}
