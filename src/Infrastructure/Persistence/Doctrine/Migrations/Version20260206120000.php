<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove is_verified column from user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN is_verified');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD is_verified BOOLEAN NOT NULL DEFAULT true');
    }
}
