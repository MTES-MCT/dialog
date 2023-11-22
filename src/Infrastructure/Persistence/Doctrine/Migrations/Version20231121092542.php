<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231121092542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization ADD siret VARCHAR(14) DEFAULT NULL');
        $this->addSql('DROP INDEX organization_name');
        $this->addSql('CREATE UNIQUE INDEX organization_siret ON organization (siret)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP siret');
        $this->addSql('CREATE UNIQUE INDEX access_request_email ON access_request (email)');
        $this->addSql('DROP INDEX organization_siret');
        $this->addSql('CREATE UNIQUE INDEX organization_name ON organization (name)');
    }
}
