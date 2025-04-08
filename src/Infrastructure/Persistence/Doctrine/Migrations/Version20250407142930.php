<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250407142930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mailing_list (uuid UUID NOT NULL, organization_uuid UUID NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, role VARCHAR(150) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_15C473AFE8766E3B ON mailing_list (organization_uuid)');
        $this->addSql('ALTER TABLE mailing_list ADD CONSTRAINT FK_15C473AFE8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailing_list DROP CONSTRAINT FK_15C473AFE8766E3B');
        $this->addSql('DROP TABLE mailing_list');
    }
}
