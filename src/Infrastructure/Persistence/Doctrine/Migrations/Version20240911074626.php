<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240911074626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE visa_model (uuid UUID NOT NULL, organization_uuid UUID DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, visas TEXT NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_B6C4492E8766E3B ON visa_model (organization_uuid)');
        $this->addSql('COMMENT ON COLUMN visa_model.visas IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE visa_model ADD CONSTRAINT FK_B6C4492E8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE visa_model DROP CONSTRAINT FK_B6C4492E8766E3B');
        $this->addSql('DROP TABLE visa_model');
    }
}
