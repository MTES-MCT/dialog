<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926100023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order ADD visa_model_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_order ADD additional_visas TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_order ADD additional_reasons TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN regulation_order.additional_visas IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN regulation_order.additional_reasons IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE regulation_order ADD CONSTRAINT FK_24FEED5D91D37028 FOREIGN KEY (visa_model_uuid) REFERENCES visa_model (uuid) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_24FEED5D91D37028 ON regulation_order (visa_model_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order DROP CONSTRAINT FK_24FEED5D91D37028');
        $this->addSql('DROP INDEX IDX_24FEED5D91D37028');
        $this->addSql('ALTER TABLE regulation_order DROP visa_model_uuid');
        $this->addSql('ALTER TABLE regulation_order DROP additional_visas');
        $this->addSql('ALTER TABLE regulation_order DROP additional_reasons');
    }
}
