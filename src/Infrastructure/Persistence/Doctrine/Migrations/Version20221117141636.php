<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221117141636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE organization (uuid UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX organization_name ON organization (name)');
        $this->addSql('CREATE TABLE organizations_users (user_uuid UUID NOT NULL, organization_uuid UUID NOT NULL, PRIMARY KEY(user_uuid, organization_uuid))');
        $this->addSql('CREATE INDEX IDX_9328CA68ABFE1C6F ON organizations_users (user_uuid)');
        $this->addSql('CREATE INDEX IDX_9328CA68E8766E3B ON organizations_users (organization_uuid)');
        $this->addSql('CREATE TABLE "user" (uuid UUID NOT NULL, full_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX user_email ON "user" (email)');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT FK_9328CA68ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES organization (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT FK_9328CA68E8766E3B FOREIGN KEY (organization_uuid) REFERENCES "user" (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT FK_9328CA68ABFE1C6F');
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT FK_9328CA68E8766E3B');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE organizations_users');
        $this->addSql('DROP TABLE "user"');
    }
}
