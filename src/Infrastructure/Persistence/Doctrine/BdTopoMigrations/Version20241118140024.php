<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241118140024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on point_de_repere.identifiant_de_secion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS point_de_repere_identifiant_de_section_idx ON point_de_repere (identifiant_de_section);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS point_de_repere_identifiant_de_section_idx');
    }
}
