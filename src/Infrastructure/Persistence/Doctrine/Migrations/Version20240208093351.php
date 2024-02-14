<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240208093351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measure ADD regulation_order_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE measure ADD CONSTRAINT FK_80071925267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_80071925267E0D5E ON measure (regulation_order_uuid)');
        $this->addSql('
            UPDATE measure SET regulation_order_uuid = l.regulation_order_uuid
            FROM measure AS m
            INNER JOIN location AS l ON l.uuid = m.location_uuid
        ');
        $this->addSql('ALTER TABLE measure ALTER regulation_order_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE measure DROP CONSTRAINT fk_80071925517be5e6');
        $this->addSql('DROP INDEX idx_80071925517be5e6');
        $this->addSql('ALTER TABLE measure DROP location_uuid');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measure DROP CONSTRAINT FK_80071925267E0D5E');
        $this->addSql('DROP INDEX IDX_80071925267E0D5E');
        $this->addSql('ALTER TABLE measure DROP regulation_order_uuid');
        $this->addSql('CREATE INDEX IDX_80071925517BE5E6 ON measure (location_uuid)');
        $this->addSql('ALTER TABLE measure ADD location_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE measure ADD CONSTRAINT FK_80071925517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
