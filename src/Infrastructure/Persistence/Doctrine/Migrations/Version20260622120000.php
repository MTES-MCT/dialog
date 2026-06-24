<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add city_code and city_label columns on location for the "ville entière" location type.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD city_code VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD city_label VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP city_code');
        $this->addSql('ALTER TABLE location DROP city_label');
    }
}
