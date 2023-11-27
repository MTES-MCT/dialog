<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231109084055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period DROP applicable_days');
        $this->addSql('ALTER TABLE period DROP start_time');
        $this->addSql('ALTER TABLE period DROP end_time');
        $this->addSql('ALTER TABLE timeslot ADD period_uuid UUID DEFAULT NULL');
        $this->addSql('
            UPDATE timeslot SET period_uuid = d.period_uuid
            FROM dailyrange AS d
            WHERE timeslot.daily_range_uuid = d.uuid
        ');
        $this->addSql('ALTER TABLE timeslot ALTER period_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE timeslot ADD CONSTRAINT FK_3BE452F71779DA08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3BE452F71779DA08 ON timeslot (period_uuid)');
        $this->addSql('ALTER TABLE timeslot DROP CONSTRAINT fk_9bd6fdc93efcdcfc');
        $this->addSql('DROP INDEX idx_3be452f73efcdcfc');
        $this->addSql('ALTER TABLE timeslot DROP daily_range_uuid');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE timeslot DROP CONSTRAINT FK_3BE452F71779DA08');
        $this->addSql('DROP INDEX IDX_3BE452F71779DA08');
        $this->addSql('ALTER TABLE timeslot DROP period_uuid');
        $this->addSql('ALTER TABLE period ADD applicable_days TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD start_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD end_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN period.applicable_days IS \'(DC2Type:array)\'');
    }
}
