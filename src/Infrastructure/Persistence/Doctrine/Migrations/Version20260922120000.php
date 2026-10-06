<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email two-factor authentication code columns to the user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD email_auth_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD email_auth_code_expires_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP email_auth_code');
        $this->addSql('ALTER TABLE "user" DROP email_auth_code_expires_at');
    }
}
