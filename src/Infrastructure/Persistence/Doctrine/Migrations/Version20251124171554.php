<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124171554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE report_address (uuid UUID NOT NULL, user_uuid UUID NOT NULL, content TEXT NOT NULL, road_type VARCHAR(40) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, has_been_contacted BOOLEAN DEFAULT false PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_6DD71281ABFE1C6F ON report_address (user_uuid)');
        $this->addSql('ALTER TABLE report_address ADD CONSTRAINT FK_6DD71281ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE report_address');
    }
}
