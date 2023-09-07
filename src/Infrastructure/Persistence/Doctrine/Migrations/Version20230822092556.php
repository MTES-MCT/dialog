<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230822092556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add regulation_order_record.source';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_record ADD source VARCHAR(32) DEFAULT \'dialog\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_record DROP source');
    }
}
