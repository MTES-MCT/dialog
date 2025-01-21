<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121142614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE token (uuid UUID NOT NULL, user_uuid UUID NOT NULL, token VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, expiration_date TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_5F37A13BABFE1C6F ON token (user_uuid)');
        $this->addSql('CREATE INDEX IDX_5F37A13B5F37A13B ON token (token)');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE token DROP CONSTRAINT FK_5F37A13BABFE1C6F');
        $this->addSql('DROP TABLE token');
    }
}
