<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230202170241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE "organizations_users"');
        $this->addSql('CREATE TABLE organizations_users (organization_uuid UUID NOT NULL, user_uuid UUID NOT NULL, PRIMARY KEY(organization_uuid, user_uuid))');
        $this->addSql('CREATE INDEX IDX_9328CA68E8766E3B ON organizations_users (organization_uuid)');
        $this->addSql('CREATE INDEX IDX_9328CA68ABFE1C6F ON organizations_users (user_uuid)');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT FK_9328CA68E8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT FK_9328CA68ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT FK_9328CA68E8766E3B');
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT FK_9328CA68ABFE1C6F');
        $this->addSql('DROP TABLE organizations_users');
        $this->addSql('ALTER TABLE "user" DROP password');
    }
}
