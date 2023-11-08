<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231030145813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD start_datetime TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD end_datetime TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD recurrence_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP include_holidays');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE period DROP start_datetime');
        $this->addSql('ALTER TABLE period DROP end_datetime');
        $this->addSql('ALTER TABLE period ADD include_holidays BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE period DROP recurrence_type');
    }
}
