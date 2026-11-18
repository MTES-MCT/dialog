<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user.account_status: moderation status for self-registered accounts (pending manual validation before login).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD account_status VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE \"user\" SET account_status = 'validated'");
        $this->addSql('ALTER TABLE "user" ALTER account_status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP account_status');
    }
}
