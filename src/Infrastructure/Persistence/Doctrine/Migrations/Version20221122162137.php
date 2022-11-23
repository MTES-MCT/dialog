<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221122162137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE regulation_condition (uuid UUID NOT NULL, traffic_regulation_uuid UUID NOT NULL, negate BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D8762B73E2272A ON regulation_condition (traffic_regulation_uuid)');
        $this->addSql('CREATE TABLE traffic_regulation (uuid UUID NOT NULL, type VARCHAR(10) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT FK_9D8762B73E2272A FOREIGN KEY (traffic_regulation_uuid) REFERENCES traffic_regulation (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT FK_9D8762B73E2272A');
        $this->addSql('DROP TABLE regulation_condition');
        $this->addSql('DROP TABLE traffic_regulation');
    }
}
