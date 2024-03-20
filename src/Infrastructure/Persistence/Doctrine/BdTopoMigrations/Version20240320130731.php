<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320130731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configure indexes on voie_nommee and route_numerotee_ou_nommee';
    }

    public function up(Schema $schema): void
    {
        // We can't use the built-in unaccent() in an index because it is not IMMUTABLE, only STABLE.
        // This is because the dictionary can theoretically change.
        // If we commit to never changing the dictionary (likely), we can use a custom IMMUTABLE wrapper.
        // Inspired by:
        // * The "Best for now" Postgres 14+ solution here: https://stackoverflow.com/a/11007216
        // * And this: https://peterullrich.com/unaccented-name-search-with-postgres-and-ecto
        $this->addSql('CREATE EXTENSION unaccent');
        $this->addSql('CREATE OR REPLACE FUNCTION public.f_unaccent(text)
            RETURNS text
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT public.unaccent(\'public.unaccent\', $1)
            $func$;
        ');

        $this->addSql('CREATE OR REPLACE FUNCTION public.f_bdtopo_voie_nommee_normalize_nom_minuscule(text)
            returns TEXT
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT lower(translate(f_unaccent($1), \'-’\', \' \'\'\'));
            $func$;
        ');

        $this->addSql('CREATE INDEX voie_nommee_normalized_nom_minuscule_code_insee_idx ON voie_nommee (f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule), code_insee);');
        $this->addSql('CREATE INDEX route_numerotee_ou_nommee_numero_gestionnaire_idx ON route_numerotee_ou_nommee (numero, gestionnaire);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX route_numerotee_ou_nommee_numero_gestionnaire_idx');
        $this->addSql('DROP INDEX voie_nommee_normalized_nom_minuscule_code_insee_idx');
        $this->addSql('DROP FUNCTION public.f_bdtopo_voie_nommee_normalize_nom_minuscule');
        $this->addSql('DROP FUNCTION public.f_unaccent');
        $this->addSql('DROP EXTENSION unaccent');
    }
}
