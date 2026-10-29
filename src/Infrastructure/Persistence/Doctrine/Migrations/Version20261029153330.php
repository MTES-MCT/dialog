<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029153330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_public_holidays_excluded on period';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE period ADD is_public_holidays_excluded BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE period DROP is_public_holidays_excluded');
    }
}
