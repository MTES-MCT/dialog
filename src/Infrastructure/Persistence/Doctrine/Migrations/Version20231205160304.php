<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231205160304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table fr_city';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fr_city (insee_code VARCHAR(5) NOT NULL, name VARCHAR(255) NOT NULL, departement VARCHAR(3) NOT NULL, PRIMARY KEY(insee_code))');
        $this->addSql('CREATE INDEX idx_fr_city_name_departement ON fr_city USING btree (lower((name)::text), departement)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fr_city');
    }
}
