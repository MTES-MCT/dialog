<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230220095706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE road_information (uuid UUID NOT NULL, supplementary_positional_description_uuid UUID NOT NULL, road_number VARCHAR(60) DEFAULT NULL, road_destination VARCHAR(60) DEFAULT NULL, road_name VARCHAR(60) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_7142E9D4AE4FD023 ON road_information (supplementary_positional_description_uuid)');
        $this->addSql('CREATE TABLE supplementary_positional_description (uuid UUID NOT NULL, location_uuid UUID NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D98191EE517BE5E6 ON supplementary_positional_description (location_uuid)');
        $this->addSql('ALTER TABLE road_information ADD CONSTRAINT FK_7142E9D4AE4FD023 FOREIGN KEY (supplementary_positional_description_uuid) REFERENCES supplementary_positional_description (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supplementary_positional_description ADD CONSTRAINT FK_D98191EE517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE road_information DROP CONSTRAINT FK_7142E9D4AE4FD023');
        $this->addSql('ALTER TABLE supplementary_positional_description DROP CONSTRAINT FK_D98191EE517BE5E6');
        $this->addSql('DROP TABLE road_information');
        $this->addSql('DROP TABLE supplementary_positional_description');
    }
}
