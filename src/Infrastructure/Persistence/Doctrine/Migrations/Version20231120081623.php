<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231120081623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_request (uuid UUID NOT NULL, full_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, organization VARCHAR(255) NOT NULL, siret VARCHAR(14) DEFAULT NULL, password VARCHAR(255) NOT NULL, consent_to_be_contacted BOOLEAN NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX access_request_email ON access_request (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE access_request');
    }
}
