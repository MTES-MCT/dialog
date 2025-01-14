<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250113103218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_user (uuid UUID NOT NULL, user_uuid UUID NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1FC6D102ABFE1C6F ON password_user (user_uuid)');
        $this->addSql('CREATE TABLE proconnect_user (uuid UUID NOT NULL, user_uuid UUID NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9010F504ABFE1C6F ON proconnect_user (user_uuid)');
        $this->addSql('ALTER TABLE password_user ADD CONSTRAINT FK_1FC6D102ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE proconnect_user ADD CONSTRAINT FK_9010F504ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('
            INSERT INTO password_user (uuid, user_uuid, password)
            SELECT uuid_generate_v4() AS uuid, u.uuid as user_uuid, u.password as password FROM public.user AS u
        ');
        $this->addSql('ALTER TABLE "user" DROP password');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE password_user DROP CONSTRAINT FK_1FC6D102ABFE1C6F');
        $this->addSql('ALTER TABLE proconnect_user DROP CONSTRAINT FK_9010F504ABFE1C6F');
        $this->addSql('DROP TABLE password_user');
        $this->addSql('DROP TABLE proconnect_user');
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) NOT NULL');
    }
}
