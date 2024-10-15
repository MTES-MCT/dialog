<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241008120435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user.last_active_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD last_active_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP last_active_at');
    }
}
