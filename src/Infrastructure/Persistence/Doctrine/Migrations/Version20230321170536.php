<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230321170536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename regulation_condition to condition';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle_characteristics DROP CONSTRAINT fk_54f8f40a9f073263');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT fk_c5b81ece9f073263');
        $this->addSql('CREATE TABLE condition (uuid UUID NOT NULL, measure_uuid UUID NOT NULL, negate BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_BDD6884396A61612 ON condition (measure_uuid)');
        $this->addSql('ALTER TABLE condition ADD CONSTRAINT FK_BDD6884396A61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT fk_9d8762b796a61612');
        $this->addSql('DROP TABLE regulation_condition');
        $this->addSql('DROP INDEX uniq_c5b81ece9f073263');
        $this->addSql('ALTER TABLE period RENAME COLUMN regulation_condition_uuid TO condition_uuid');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE638B0587 FOREIGN KEY (condition_uuid) REFERENCES condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C5B81ECE638B0587 ON period (condition_uuid)');
        $this->addSql('DROP INDEX uniq_54f8f40a9f073263');
        $this->addSql('ALTER TABLE vehicle_characteristics RENAME COLUMN regulation_condition_uuid TO condition_uuid');
        $this->addSql('ALTER TABLE vehicle_characteristics ADD CONSTRAINT FK_54F8F40A638B0587 FOREIGN KEY (condition_uuid) REFERENCES condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54F8F40A638B0587 ON vehicle_characteristics (condition_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE638B0587');
        $this->addSql('ALTER TABLE vehicle_characteristics DROP CONSTRAINT FK_54F8F40A638B0587');
        $this->addSql('CREATE TABLE regulation_condition (uuid UUID NOT NULL, measure_uuid UUID NOT NULL, negate BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_9d8762b796a61612 ON regulation_condition (measure_uuid)');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT fk_9d8762b796a61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE condition DROP CONSTRAINT FK_BDD6884396A61612');
        $this->addSql('DROP TABLE condition');
        $this->addSql('DROP INDEX UNIQ_54F8F40A638B0587');
        $this->addSql('ALTER TABLE vehicle_characteristics RENAME COLUMN condition_uuid TO regulation_condition_uuid');
        $this->addSql('ALTER TABLE vehicle_characteristics ADD CONSTRAINT fk_54f8f40a9f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_54f8f40a9f073263 ON vehicle_characteristics (regulation_condition_uuid)');
        $this->addSql('DROP INDEX UNIQ_C5B81ECE638B0587');
        $this->addSql('ALTER TABLE period RENAME COLUMN condition_uuid TO regulation_condition_uuid');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT fk_c5b81ece9f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_c5b81ece9f073263 ON period (regulation_condition_uuid)');
    }
}
