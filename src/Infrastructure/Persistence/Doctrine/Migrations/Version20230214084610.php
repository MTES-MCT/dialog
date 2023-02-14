<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230214084610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to FKs between regulation_condition and location, overallPeriod and vehicle_characteristics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB9F073263');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB9F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE overall_period DROP CONSTRAINT FK_A58D9F529F073263');
        $this->addSql('ALTER TABLE overall_period ADD CONSTRAINT FK_A58D9F529F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE vehicle_characteristics DROP CONSTRAINT FK_54F8F40A9F073263');
        $this->addSql('ALTER TABLE vehicle_characteristics ADD CONSTRAINT FK_54F8F40A9F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE overall_period DROP CONSTRAINT fk_a58d9f529f073263');
        $this->addSql('ALTER TABLE overall_period ADD CONSTRAINT fk_a58d9f529f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE vehicle_characteristics DROP CONSTRAINT fk_54f8f40a9f073263');
        $this->addSql('ALTER TABLE vehicle_characteristics ADD CONSTRAINT fk_54f8f40a9f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT fk_5e9e89cb9f073263');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT fk_5e9e89cb9f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
