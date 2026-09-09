<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add token.created_at and token.used_at: used tokens are now kept until their expiration date to keep track of password reset requests (#2068)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE token ADD created_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE token ADD used_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        // Les tokens existants ont tous été créés avec une durée de validité d'un mois.
        $this->addSql("UPDATE token SET created_at = expiration_date - INTERVAL '1 month'");
        $this->addSql('ALTER TABLE token ALTER created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE token DROP created_at');
        $this->addSql('ALTER TABLE token DROP used_at');
    }
}
