<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250115164924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE regulation_order_history DROP CONSTRAINT FK_E47F415B267E0D5E');
        $this->addSql('ALTER TABLE regulation_order_history DROP CONSTRAINT FK_E47F415BABFE1C6F');
        $this->addSql('ALTER TABLE regulation_order_history ALTER regulation_order_uuid DROP NOT NULL');
        $this->addSql('ALTER TABLE regulation_order_history ALTER user_uuid DROP NOT NULL');
        $this->addSql('ALTER TABLE regulation_order_history ADD CONSTRAINT FK_E47F415B267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_order_history ADD CONSTRAINT FK_E47F415BABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_history DROP CONSTRAINT fk_e47f415b267e0d5e');
        $this->addSql('ALTER TABLE regulation_order_history DROP CONSTRAINT fk_e47f415babfe1c6f');
        $this->addSql('ALTER TABLE regulation_order_history ALTER regulation_order_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order_history ALTER user_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order_history ADD CONSTRAINT fk_e47f415b267e0d5e FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_order_history ADD CONSTRAINT fk_e47f415babfe1c6f FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
