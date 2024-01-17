<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115141540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD road_type VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD administrator VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD road_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ALTER city_code DROP NOT NULL');
        $this->addSql('ALTER TABLE location ALTER city_label DROP NOT NULL');

        $this->addSql("UPDATE location SET road_type = 'lane'");
        $this->addSql('ALTER TABLE location ALTER road_type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP road_type');
        $this->addSql('ALTER TABLE location DROP administrator');
        $this->addSql('ALTER TABLE location DROP road_number');
        $this->addSql('ALTER TABLE location ALTER city_code SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER city_label SET NOT NULL');
    }
}
