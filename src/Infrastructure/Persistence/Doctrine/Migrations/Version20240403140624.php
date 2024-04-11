<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240403140624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD from_point_number VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD from_side VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD from_abscissa INT DEFAULT 0');
        $this->addSql('ALTER TABLE location ADD to_point_number VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD to_side VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD to_abscissa INT DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP from_point_number');
        $this->addSql('ALTER TABLE location DROP from_side');
        $this->addSql('ALTER TABLE location DROP from_abscissa');
        $this->addSql('ALTER TABLE location DROP to_point_number');
        $this->addSql('ALTER TABLE location DROP to_side');
        $this->addSql('ALTER TABLE location DROP to_abscissa');
    }
}
