<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010092809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dailyrange ADD period_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE dailyrange ADD CONSTRAINT FK_8AA435C71779DA08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8AA435C71779DA08 ON dailyrange (period_uuid)');
        $this->addSql('ALTER INDEX idx_9bd6fdc93efcdcfc RENAME TO IDX_3BE452F73EFCDCFC');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dailyrange DROP CONSTRAINT FK_8AA435C71779DA08');
        $this->addSql('DROP INDEX UNIQ_8AA435C71779DA08');
        $this->addSql('ALTER TABLE dailyrange DROP period_uuid');
        $this->addSql('ALTER INDEX idx_3be452f73efcdcfc RENAME TO idx_9bd6fdc93efcdcfc');
    }
}
