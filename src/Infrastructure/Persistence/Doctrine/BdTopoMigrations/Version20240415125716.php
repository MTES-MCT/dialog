<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240415125716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE voie_nommee ADD COLUMN nom_minuscule_search tsvector GENERATED ALWAYS AS (to_tsvector('french', nom_minuscule)) STORED");
        $this->addSql('CREATE INDEX voie_nommee_nom_minuscule_search_idx ON voie_nommee USING GIN(nom_minuscule_search)');
        $this->addSql('CREATE INDEX voie_nommee_code_insee_idx ON voie_nommee (code_insee)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS voie_nommee_nom_minuscule_search_idx');
        $this->addSql('DROP INDEX IF EXISTS voie_nommee_code_insee_idx');
        $this->addSql('ALTER TABLE voie_nommee DROP COLUMN nom_minuscule_search');
    }
}
