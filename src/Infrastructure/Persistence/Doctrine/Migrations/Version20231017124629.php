<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231017124629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD start_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD end_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP include_holidays');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE period ADD include_holidays BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE period DROP start_date');
        $this->addSql('ALTER TABLE period DROP end_date');
    }
}
