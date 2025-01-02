<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241219094151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add column organization.created_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization ADD created_at TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization DROP created_at');
    }
}
