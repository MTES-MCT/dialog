<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250114151655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE regulation_order_history (uuid UUID NOT NULL, regulation_order_uuid UUID NOT NULL, user_uuid UUID NOT NULL, action VARCHAR(20) NOT NULL, date TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_E47F415B267E0D5E ON regulation_order_history (regulation_order_uuid)');
        $this->addSql('CREATE INDEX IDX_E47F415BABFE1C6F ON regulation_order_history (user_uuid)');
        $this->addSql('ALTER TABLE regulation_order_history ADD CONSTRAINT FK_E47F415B267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_order_history ADD CONSTRAINT FK_E47F415BABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_history DROP CONSTRAINT FK_E47F415B267E0D5E');
        $this->addSql('ALTER TABLE regulation_order_history DROP CONSTRAINT FK_E47F415BABFE1C6F');
        $this->addSql('DROP TABLE regulation_order_history');
    }
}
