<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221202102535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT fk_9d8762b73e2272a');
        $this->addSql('DROP TABLE traffic_regulation');
        $this->addSql('DROP INDEX uniq_9d8762b73e2272a');
        $this->addSql('ALTER TABLE regulation_condition DROP traffic_regulation_uuid');
        $this->addSql('ALTER TABLE regulation_order ADD regulation_condition_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE regulation_order ADD CONSTRAINT FK_24FEED5D9F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_24FEED5D9F073263 ON regulation_order (regulation_condition_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE traffic_regulation (uuid UUID NOT NULL, type VARCHAR(10) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('ALTER TABLE regulation_condition ADD traffic_regulation_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT fk_9d8762b73e2272a FOREIGN KEY (traffic_regulation_uuid) REFERENCES traffic_regulation (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_9d8762b73e2272a ON regulation_condition (traffic_regulation_uuid)');
        $this->addSql('ALTER TABLE regulation_order DROP CONSTRAINT FK_24FEED5D9F073263');
        $this->addSql('DROP INDEX UNIQ_24FEED5D9F073263');
        $this->addSql('ALTER TABLE regulation_order DROP regulation_condition_uuid');
    }
}
