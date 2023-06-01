<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230523131031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ALTER start_time TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE period ALTER end_time TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN period.start_time IS NULL');
        $this->addSql('COMMENT ON COLUMN period.end_time IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ALTER start_time TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE period ALTER end_time TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('COMMENT ON COLUMN period.start_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.end_time IS \'(DC2Type:datetimetz_immutable)\'');
    }
}
