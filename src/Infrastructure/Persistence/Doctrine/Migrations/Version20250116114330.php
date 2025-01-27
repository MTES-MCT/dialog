<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116114330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE regulation_order_history (uuid UUID NOT NULL, regulation_order_uuid VARCHAR(50) NOT NULL, user_uuid VARCHAR(50) NOT NULL, action VARCHAR(20) NOT NULL, date TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_E47F415B267E0D5E ON regulation_order_history (regulation_order_uuid)');
        $this->addSql('CREATE INDEX IDX_E47F415BABFE1C6F ON regulation_order_history (user_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE regulation_order_history');
    }
}
