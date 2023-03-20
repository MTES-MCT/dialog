<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230315101340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE measure (uuid UUID NOT NULL, location_uuid UUID NOT NULL, type VARCHAR(16) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_80071925517BE5E6 ON measure (location_uuid)');
        $this->addSql('ALTER TABLE measure ADD CONSTRAINT FK_80071925517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measure DROP CONSTRAINT FK_80071925517BE5E6');
        $this->addSql('DROP TABLE measure');
    }
}
