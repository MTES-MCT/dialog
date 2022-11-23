<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221124143032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE condition_set (uuid UUID NOT NULL, regulation_condition_uuid UUID DEFAULT NULL, operator VARCHAR(5) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_AF4A2C659F073263 ON condition_set (regulation_condition_uuid)');
        $this->addSql('ALTER TABLE condition_set ADD CONSTRAINT FK_AF4A2C659F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_condition ADD parent_condition_set_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_condition ALTER traffic_regulation_uuid DROP NOT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT FK_9D8762B7299EA18A FOREIGN KEY (parent_condition_set_uuid) REFERENCES condition_set (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D8762B7299EA18A ON regulation_condition (parent_condition_set_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT FK_9D8762B7299EA18A');
        $this->addSql('ALTER TABLE condition_set DROP CONSTRAINT FK_AF4A2C659F073263');
        $this->addSql('DROP TABLE condition_set');
        $this->addSql('DROP INDEX UNIQ_9D8762B7299EA18A');
        $this->addSql('ALTER TABLE regulation_condition DROP parent_condition_set_uuid');
        $this->addSql('ALTER TABLE regulation_condition ALTER traffic_regulation_uuid SET NOT NULL');
    }
}
