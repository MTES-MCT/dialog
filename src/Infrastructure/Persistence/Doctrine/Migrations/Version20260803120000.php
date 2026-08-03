<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add inactivity_email_sent_at to user to send a single inactivity reminder email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD inactivity_email_sent_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP inactivity_email_sent_at');
    }
}
