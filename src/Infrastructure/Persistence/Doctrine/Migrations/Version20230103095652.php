<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230103095652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE location_condition (uuid UUID NOT NULL, regulation_condition_uuid UUID NOT NULL, geometry geometry(GEOMETRY, 0) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F3035E359F073263 ON location_condition (regulation_condition_uuid)');
        $this->addSql('ALTER TABLE location_condition ADD CONSTRAINT FK_F3035E359F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location_condition DROP CONSTRAINT FK_F3035E359F073263');
        $this->addSql('DROP TABLE location_condition');
    }
}
