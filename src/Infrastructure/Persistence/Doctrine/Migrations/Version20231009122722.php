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
        $this->addSql('CREATE TABLE dailyrange (uuid UUID NOT NULL, applicable_days TEXT NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('COMMENT ON COLUMN dailyrange.applicable_days IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE timeslot (uuid UUID NOT NULL, daily_range_uuid UUID NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_9BD6FDC93EFCDCFC ON timeslot (daily_range_uuid)');
        $this->addSql('ALTER TABLE timeslot ADD CONSTRAINT FK_9BD6FDC93EFCDCFC FOREIGN KEY (daily_range_uuid) REFERENCES dailyrange (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE timeslot DROP CONSTRAINT FK_9BD6FDC93EFCDCFC');
        $this->addSql('DROP TABLE dailyrange');
        $this->addSql('DROP TABLE timeslot');
    }
}
