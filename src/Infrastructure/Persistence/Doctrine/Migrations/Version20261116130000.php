<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table anct_territory_contact (ANCT official e-mail whitelist)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE anct_territory_contact (siret VARCHAR(14) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(siret))');
        $this->addSql('CREATE INDEX idx_anct_territory_contact_siret_email ON anct_territory_contact (siret, email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE anct_territory_contact');
    }
}
