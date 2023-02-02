<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230201165801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop regulation_order_record.last_filled_step';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_record DROP last_filled_step');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_order_record ADD last_filled_step SMALLINT DEFAULT 1 NOT NULL');
    }
}
