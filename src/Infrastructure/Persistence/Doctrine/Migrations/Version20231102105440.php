<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231102105440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback (uuid UUID NOT NULL, user_uuid UUID NOT NULL, content TEXT NOT NULL, consent_to_be_contacted BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_D2294458ABFE1C6F ON feedback (user_uuid)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP CONSTRAINT FK_D2294458ABFE1C6F');
        $this->addSql('DROP TABLE feedback');
    }
}
