<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250707124212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order DROP CONSTRAINT fk_24feed5d91d37028
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visa_model DROP CONSTRAINT fk_b6c4492e8766e3b
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE visa_model
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_24feed5d91d37028
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order DROP visa_model_uuid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order DROP additional_visas
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order DROP additional_reasons
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE visa_model (uuid UUID NOT NULL, organization_uuid UUID DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, visas TEXT NOT NULL, PRIMARY KEY(uuid))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_b6c4492e8766e3b ON visa_model (organization_uuid)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN visa_model.visas IS '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visa_model ADD CONSTRAINT fk_b6c4492e8766e3b FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order ADD visa_model_uuid UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order ADD additional_visas TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order ADD additional_reasons TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN regulation_order.additional_visas IS '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN regulation_order.additional_reasons IS '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order ADD CONSTRAINT fk_24feed5d91d37028 FOREIGN KEY (visa_model_uuid) REFERENCES visa_model (uuid) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_24feed5d91d37028 ON regulation_order (visa_model_uuid)
        SQL);
    }
}
