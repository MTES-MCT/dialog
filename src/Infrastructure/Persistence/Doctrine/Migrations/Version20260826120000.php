<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_mandataire to organizations_users and invitation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organizations_users ADD is_mandataire BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE organizations_users ALTER COLUMN is_mandataire DROP DEFAULT');
        $this->addSql('ALTER TABLE invitation ADD is_mandataire BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE invitation ALTER COLUMN is_mandataire DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organizations_users DROP is_mandataire');
        $this->addSql('ALTER TABLE invitation DROP is_mandataire');
    }
}
