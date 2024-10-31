<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241029090022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signing_authority (uuid UUID NOT NULL, organization_uuid UUID NOT NULL, name VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL, made_in VARCHAR(100) NOT NULL, signatory_name VARCHAR(100) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_518738CFE8766E3B ON signing_authority (organization_uuid)');
        $this->addSql('ALTER TABLE signing_authority ADD CONSTRAINT FK_518738CFE8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signing_authority DROP CONSTRAINT FK_518738CFE8766E3B');
        $this->addSql('DROP TABLE signing_authority');
    }
}
