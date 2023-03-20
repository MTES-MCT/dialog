<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230320153658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop overall_period';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE period DROP CONSTRAINT fk_c5b81ece2ada3265');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT fk_c5b81ece1276c856');
        $this->addSql('ALTER TABLE overall_period DROP CONSTRAINT fk_a58d9f529f073263');
        $this->addSql('DROP TABLE overall_period');
        $this->addSql('DROP INDEX idx_c5b81ece1276c856');
        $this->addSql('DROP INDEX idx_c5b81ece2ada3265');
        $this->addSql('ALTER TABLE period DROP overall_valid_period_uuid');
        $this->addSql('ALTER TABLE period DROP overall_exception_period_uuid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE overall_period (uuid UUID NOT NULL, regulation_condition_uuid UUID NOT NULL, start_date TIMESTAMP(0) WITH TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, start_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, end_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX uniq_a58d9f529f073263 ON overall_period (regulation_condition_uuid)');
        $this->addSql('COMMENT ON COLUMN overall_period.start_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN overall_period.end_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE overall_period ADD CONSTRAINT fk_a58d9f529f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period ADD overall_valid_period_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD overall_exception_period_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT fk_c5b81ece2ada3265 FOREIGN KEY (overall_valid_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT fk_c5b81ece1276c856 FOREIGN KEY (overall_exception_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_c5b81ece1276c856 ON period (overall_exception_period_uuid)');
        $this->addSql('CREATE INDEX idx_c5b81ece2ada3265 ON period (overall_valid_period_uuid)');
    }
}
