<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250204083125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invitation (uuid UUID NOT NULL, owner_uuid UUID NOT NULL, organization_uuid UUID NOT NULL, full_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_F11D61A247D93336 ON invitation (owner_uuid)');
        $this->addSql('CREATE INDEX IDX_F11D61A2E8766E3B ON invitation (organization_uuid)');
        $this->addSql('CREATE INDEX IDX_F11D61A2E7927C74 ON invitation (email)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A247D93336 FOREIGN KEY (owner_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2E8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A247D93336');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A2E8766E3B');
        $this->addSql('DROP TABLE invitation');
    }
}
