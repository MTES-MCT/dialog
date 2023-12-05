<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231205115752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create communes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE data_communes (code VARCHAR(5) NOT NULL, nom VARCHAR(255) NOT NULL, departement VARCHAR(3) NOT NULL, region VARCHAR(3) NOT NULL, codes_postaux VARCHAR(5)[] NOT NULL, PRIMARY KEY(code, nom))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE data_communes');
    }
}
