<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306142031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signing_authority ADD road_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signing_authority ADD city_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signing_authority ADD city_label VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE signing_authority DROP road_name');
        $this->addSql('ALTER TABLE signing_authority DROP city_code');
        $this->addSql('ALTER TABLE signing_authority DROP city_label');
    }
}
