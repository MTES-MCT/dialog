<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231009122722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dailyRange (uuid UUID NOT NULL, applicable_days TEXT NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('COMMENT ON COLUMN dailyRange.applicable_days IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE timeSlot (uuid UUID NOT NULL, daily_range_uuid UUID NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_9BD6FDC93EFCDCFC ON timeSlot (daily_range_uuid)');
        $this->addSql('ALTER TABLE timeSlot ADD CONSTRAINT FK_9BD6FDC93EFCDCFC FOREIGN KEY (daily_range_uuid) REFERENCES dailyRange (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA topology');
        $this->addSql('CREATE SCHEMA tiger');
        $this->addSql('CREATE SCHEMA tiger_data');
        $this->addSql('ALTER TABLE timeSlot DROP CONSTRAINT FK_9BD6FDC93EFCDCFC');
        $this->addSql('DROP TABLE dailyRange');
        $this->addSql('DROP TABLE timeSlot');
    }
}
