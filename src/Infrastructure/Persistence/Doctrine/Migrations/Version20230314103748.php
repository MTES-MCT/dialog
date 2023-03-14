<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230314103748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP CONSTRAINT fk_5e9e89cb9f073263');
        $this->addSql('DROP INDEX uniq_5e9e89cb9f073263');
        $this->addSql('ALTER TABLE location ADD regulation_order_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE location DROP regulation_condition_uuid');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5E9E89CB267E0D5E ON location (regulation_order_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB267E0D5E');
        $this->addSql('DROP INDEX IDX_5E9E89CB267E0D5E');
        $this->addSql('ALTER TABLE location ADD regulation_condition_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE location DROP regulation_order_uuid');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT fk_5e9e89cb9f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_5e9e89cb9f073263 ON location (regulation_condition_uuid)');
    }
}
